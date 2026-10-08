# Multi-Tenancy-Implementierungsplan

## Zielbild

LazyTown wird zu einer Single-Database-SaaS-Anwendung für mehrere Vereine. Jeder Verein ist über eine eigene Subdomain erreichbar:

```text
https://{verein-slug}.lazytown.test
```

Ein Benutzer darf die Tenant-Subdomain nur verwenden, wenn er eine aktive Membership in genau diesem Verein besitzt. Module werden pro Verein aktiviert und serverseitig wie clientseitig berücksichtigt.

## Ergebnis der Bestandsprüfung

Der Plan ist mit dem bestehenden Projekt grundsätzlich umsetzbar. Es gibt bereits wichtige Bausteine:

- `App\Models\Team` repräsentiert bereits einen Verein und besitzt einen eindeutigen `slug`.
- `team_members` mit `App\Models\Membership` bildet die Benutzer-Vereins-Zuordnung ab.
- `User::teams()`, `belongsToTeam()`, `teamRole()` und `currentTeam()` kapseln bereits einen großen Teil der Teamlogik.
- `EnsureTeamMembership`, `EnsureClubAccess`, `SetPermissionTeam` und `SetTeamUrlDefaults` sind vorhandene Middleware für teambezogenen Zugriff.
- `spatie/laravel-permission` ist bereits installiert und wird mit Team-Scope verwendet.
- Inertia stellt über `HandleInertiaRequests` bereits `currentTeam` und `teams` als globale Props bereit.
- Die Anwendung verwendet Laravel 13, Inertia 3 und Vue 3.

### Wichtigste Anpassungen gegenüber dem allgemeinen Plan

1. Ein separates `Tenant`-Model ist zunächst nicht erforderlich. `Team` wird als Tenant-Modell verwendet oder fachlich in `Tenant` umbenannt, falls später Teams innerhalb eines Vereins benötigt werden.
2. `current_team` als URL-Parameter und die heutigen pfadbasierten Team-Routen sind nicht mehr der primäre Tenant-Kontext. Der Hostname wird zum verbindlichen Kontext.
3. `team_members` bleibt die Membership-Tabelle. Eine zusätzliche `tenant_user`-Tabelle wäre doppelte Datenhaltung.
4. Das vorhandene Spatie-Permission-Team-Scope wird weiterverwendet, muss aber auf den über die Subdomain aktivierten Verein gesetzt werden.
5. Globale Benutzerrollen wie `User::$roles` dürfen keine Tenant-Berechtigungen ersetzen. Vereinsrollen müssen aus der Membership des aktiven Vereins kommen.
6. `spatie/laravel-multitenancy` ist aktuell nicht in `composer.json` enthalten und muss vor der Implementierung versionsbezogen geprüft und installiert werden.

## Architekturentscheidung

### Empfehlung: Single-Database-Multi-Tenancy

Zunächst wird eine gemeinsame Datenbank verwendet:

- Vereine liegen in `teams` beziehungsweise später `tenants`.
- Benutzer bleiben global.
- Memberships liegen in `team_members`.
- Tenant-spezifische Fachdatensätze erhalten eine `team_id` beziehungsweise `tenant_id`.
- Aktivierte Module werden über eine Pivot-Tabelle gespeichert.

Diese Variante passt zur bestehenden Datenstruktur und reduziert Komplexität bei Migrationen, Backups, lokaler Entwicklung und Deployment.

### Datenbank pro Tenant

Eine eigene Datenbank pro Verein wird zunächst nicht umgesetzt. Sie sollte nur bei deutlich höheren Anforderungen eingeführt werden, etwa bei sehr großen Tenants, strikter physischer Datenisolierung oder individuellen Backup-/Restore-Anforderungen.

Die Fachlogik sollte trotzdem so strukturiert werden, dass ein späterer Wechsel nicht alle Controller und Vue-Seiten betrifft.

## Datenmodell und Migrationen

### 1. Bestehendes `teams`-Modell als Tenant-Basis

Das bestehende `teams`-Schema enthält bereits:

- `id`
- `name`
- eindeutigen `slug`
- `is_personal`
- Zahlungsstatus
- Soft Deletes

Für die Tenant-Funktionalität sind zusätzlich beziehungsweise fachlich zu prüfen:

- `is_active` oder ein klarer Status für deaktivierte Vereine
- optional `settings` als JSON
- optional `custom_domain` für spätere eigene Domains
- optional `subdomain` nur dann, wenn sie nicht aus dem Slug abgeleitet wird

Der Slug muss host-sicher validiert werden. Änderungen am Vereinsnamen dürfen nicht unkontrolliert eine produktive Subdomain ändern. Deshalb sollte später zwischen Anzeigename und dauerhaftem Subdomain-Slug unterschieden werden.

### 2. Bestehende Membership-Tabelle weiterverwenden

`team_members` ist bereits die richtige Zuordnung von Benutzer zu Verein. Die bestehende Struktur mit `team_id`, `user_id`, `role` und `status` wird erweitert beziehungsweise geprüft:

- zusammengesetzter Unique-Index auf `team_id` und `user_id`
- Index auf `user_id` und `status`
- Statuswerte mindestens `active`, `pending`, `suspended`
- tenantbezogene Rolle in `role`
- keine alleinige Autorisierung über globale Benutzerrollen

Eine neue `tenant_user`-Tabelle wird nicht angelegt, solange `Team` der Tenant ist.

### 3. `features`

Neue Tabelle für den globalen Feature-Katalog:

```text
features
- id
- key                 eindeutig, stabil und programmierbar
- name
- description
- is_active
- configuration       optionales JSON
- created_at
- updated_at
```

Beispiel-Keys:

```text
teams
training
competitions
statistics
billing
```

Der technische `key` darf nach Veröffentlichung nicht leichtfertig geändert werden.

### 4. `team_feature` beziehungsweise `tenant_feature`

Da `Team` vorerst der Tenant ist, ist `team_feature` im bestehenden Projekt die konsistente Benennung:

```text
team_feature
- team_id
- feature_id
- status
- enabled_at
- expires_at
- configuration       optionales JSON
- created_at
- updated_at
```

Empfehlungen:

- Unique-Index auf `team_id` und `feature_id`
- Foreign Keys mit passendem Löschverhalten
- Statuswerte wie `active`, `trial`, `expired`, `cancelled`
- Deaktivieren statt Löschen, damit Historie und Daten erhalten bleiben

Falls das Projekt `Team` später in `Tenant` umbenennt, wird die Tabelle entsprechend `tenant_feature` genannt.

### 5. Optionale Abrechnung

Billing und Feature-Berechtigung werden getrennt gehalten. Bei echter SaaS-Abrechnung kommen später beispielsweise hinzu:

```text
plans
subscriptions
subscription_items
```

Die Zugriffskontrolle prüft weiterhin eine konsolidierte Aktivierung. Das Billing-System darf nicht direkt in jedem Controller abgefragt werden.

## Paket- und Framework-Setup

### `spatie/laravel-multitenancy`

Vor der Installation:

1. Installierte Laravel- und Paketversionen prüfen.
2. Kompatible Version von `spatie/laravel-multitenancy` auswählen.
3. Paket installieren und Konfiguration veröffentlichen.
4. Bestehendes `Team`-Model als Tenant-Model integrieren oder eine begründete Umbenennung auf `Tenant` durchführen.
5. Tenant Finder für Hostnamen konfigurieren.
6. Tenant-unaware Modelle wie Benutzer, Feature-Katalog und zentrale Abrechnung festlegen.
7. Tenant-aware Modelle wie Vereinsdaten und Vereinsmodule festlegen.
8. Tenant-Tasks, Cache- und Queue-Verhalten dokumentieren.

Das Paket darf nicht parallel zu einer zweiten, selbstgebauten Tenant-Auflösung arbeiten. Die zentrale Quelle für den aktiven Verein soll der Paket-Kontext sein.

### Aktive Tenant-Auflösung

Der Hostname wird verarbeitet:

```text
abc.lazytown.test -> Team::where('slug', 'abc')->first()
```

Folgende Fälle benötigen definierte Antworten:

- Hauptdomain ohne Tenant
- unbekannte Subdomain
- deaktivierter Verein
- ungültiger Hostname
- lokale Entwicklungsdomain
- spätere Custom Domain

Der Tenant darf niemals aus einem frei übergebenen Formularfeld oder nur aus einem URL-Parameter aktiviert werden.

## Routing und Middleware

### Domain-Gruppen

Die zentrale Domain bleibt für globale Funktionen zuständig:

```text
lazytown.test
```

Dort liegen beispielsweise Login, Registrierung, Profil und Vereins-Onboarding.

Tenant-Routen werden über eine Subdomain-Gruppe organisiert:

```text
{team}.lazytown.test
```

Auf dieser Domain liegen Dashboard, Vereinsverwaltung und aktivierte Fachmodule.

Die heutige Struktur mit Routen wie `settings/teams/{team}` und `current_team` bleibt höchstens für Übergang, Verwaltung oder Migration bestehen. Sie darf nicht parallel einen zweiten, widersprüchlichen Tenant-Kontext erzeugen.

### Middleware-Reihenfolge

Die Zielreihenfolge lautet:

```text
tenant aus Hostname bestimmen
-> Tenant aktivieren
-> Tenant aktiv und gültig?
-> auth
-> aktive Membership im Tenant
-> Rolle und Berechtigung
-> Feature aktiviert?
-> Controller oder Inertia-Seite
```

Dafür werden die vorhandenen Middleware schrittweise angepasst:

- `EnsureClubAccess`: nicht mehr global irgendeine Vereinsmitgliedschaft prüfen, sondern Membership im aktiven Tenant.
- `EnsureTeamMembership`: primär den bereits aktivierten Tenant verwenden und nicht zusätzlich einen konkurrierenden URL-Tenant auflösen.
- `SetPermissionTeam`: Permission-Team-ID aus dem aktiven Tenant-Kontext setzen.
- `SetTeamUrlDefaults`: für Subdomain-Routen zurückbauen oder nur noch für Legacy-/Administrationsrouten verwenden.
- `HandleInertiaRequests`: aktiven Tenant, Membership, Berechtigungen und aktivierte Features teilen.

### Autorisierung

Die Subdomain liefert nur den Kandidaten-Tenant. Der Zugriff wird erst gewährt, wenn:

- der Benutzer authentifiziert ist,
- der Tenant existiert und aktiv ist,
- eine aktive Membership des Benutzers für diesen Tenant existiert,
- die konkrete Policy beziehungsweise Berechtigung erfüllt ist.

Unbekannte Tenants können als 404 behandelt werden. Eine fehlende Membership sollte je nach gewünschter Informationspreisgabe 403 oder 404 liefern.

## Backend-Feature-System

### Feature-Registry

Feature-Keys werden zentral definiert, beispielsweise in einer PHP-Registry oder einem Enum. Jeder Key besitzt dort mindestens:

- technischen Schlüssel
- Anzeigenamen
- Beschreibung
- optional erforderliche Berechtigung
- optionales Konfigurationsschema

Datenbankeinträge bilden verfügbare und aktivierte Module ab; Controller sollen keine verstreuten String-Konstanten enthalten.

### Feature-Service

Ein zentraler Service kapselt die Prüfung:

```text
teamHasFeature(team, featureKey)
userCanUseFeature(user, team, featureKey)
featureConfiguration(team, featureKey)
```

Direkte Pivot-Abfragen in Controllern und Vue-Seiten werden vermieden.

### Middleware, Gates und Policies

Für vollständige Routenbereiche wird Feature-Middleware verwendet:

```text
feature:training
feature:statistics
```

Policies prüfen weiterhin die individuelle Berechtigung. Beide Ebenen bleiben getrennt:

- Feature: Hat der Verein das Modul aktiviert?
- Policy: Darf dieser Benutzer diese Aktion ausführen?

Beispiel:

```text
Training verwalten =
  aktive Membership
  + Berechtigung training.manage
  + Feature training aktiv
```

Das bestehende `TeamPolicy`- und `TeamPermission`-System wird erweitert, nicht ersetzt. Die bereits vorhandene Spatie-Team-Scope-Logik muss vor jedem Berechtigungscheck den aktiven Verein erhalten.

## Vue- und Inertia-Integration

### Shared Props

`HandleInertiaRequests` stellt für den aktiven Tenant mindestens bereit:

```text
tenant/team
membership
permissions
features
featureConfiguration
```

Die vorhandenen Props `currentTeam` und `teams` können zunächst kompatibel weitergeführt werden. Langfristig sollte `currentTeam` den aktiven Subdomain-Tenant repräsentieren und nicht mehr einen unabhängig gewählten URL-Kontext.

### Frontend-Helfer

Die Feature-Prüfungen werden zentral gekapselt, beispielsweise durch:

- `hasFeature(key)`
- `can(permission)`
- `useFeature()`
- eine wiederverwendbare Feature-Gate-Komponente

Navigation und sichtbare Aktionen prüfen Feature und Berechtigung. Das Frontend blendet deaktivierte Module aus, ersetzt aber niemals Backend-Autorisierung.

### Direkte Aufrufe und Fehler

Ein direkter Aufruf einer deaktivierten Route muss serverseitig mit einer geeigneten 403-/404-Antwort oder einer dedizierten Inertia-Fehlerseite abgewiesen werden. Ein ausschließlich ausgeblendeter Menüpunkt ist kein Schutz.

## Verwaltungsbereich für Vereinsinhaber

Vereinsbesitzer beziehungsweise berechtigte Administratoren verwalten:

- Vereinsname und Subdomain-Slug
- Mitglieder und Membership-Status
- Rollen und Berechtigungen
- aktivierte Features
- Feature-Konfiguration
- später Abonnements und Rechnungen

Feature-Aktivierungen müssen serverseitig geprüft, transaktional und idempotent sein. Beim Deaktivieren werden Fachdatensätze nicht automatisch gelöscht.

## Bestehende Dateien und erwartete Anpassung

| Bestehender Bereich | Geplante Anpassung |
| --- | --- |
| `app/Models/Team.php` | Tenant-Vertrag/-Konfiguration, Aktivstatus und Feature-Beziehungen |
| `app/Models/Membership.php` | aktive Membership und tenantbezogene Rollen als zentrale Zugriffsbasis |
| `app/Concerns/HasTeams.php` | aktiven Host-Tenant berücksichtigen; `current_team` nur als Übergang behandeln |
| `app/Http/Middleware/EnsureClubAccess.php` | aktiven Tenant statt irgendeines Vereins prüfen |
| `app/Http/Middleware/EnsureTeamMembership.php` | Tenant-Kontext aus Hostname/Paket verwenden |
| `app/Http/Middleware/SetPermissionTeam.php` | Spatie-Scope aus aktivem Tenant setzen |
| `app/Http/Middleware/SetTeamUrlDefaults.php` | für Subdomain-Routing ersetzen oder auf Legacy-Routen begrenzen |
| `bootstrap/app.php` | neue Middleware-Aliase und Reihenfolge registrieren |
| `routes/web.php`, `routes/settings.php` | globale und Tenant-Domain-Routen trennen |
| `app/Policies/TeamPolicy.php` | Tenant-Kontext und Feature-Prüfungen ergänzen |
| `app/Http/Middleware/HandleInertiaRequests.php` | Tenant-, Membership-, Permission- und Feature-Props teilen |
| `resources/js` | zentrale Feature-/Permission-Composables und Navigation anpassen |
| `composer.json` | `spatie/laravel-multitenancy` nach Versionsprüfung ergänzen |
| `database/migrations` | Tenantstatus, Features und Feature-Pivot ergänzen |

## Umsetzungsreihenfolge

### Phase 1: Architektur festziehen

1. Prüfen, ob `Team` dauerhaft der Verein/Tenant bleibt.
2. Festlegen, ob zukünftige Teams innerhalb eines Vereins benötigt werden.
3. Tenant-Daten, globale Daten und tenantbezogene Daten katalogisieren.
4. Hostname- und lokale Domain-Strategie festlegen.
5. Feature-Katalog und Berechtigungsnamen definieren.

### Phase 2: Tenant-Grundlage

1. Paketversion und Kompatibilität prüfen.
2. `spatie/laravel-multitenancy` installieren.
3. `Team` als Tenant integrieren.
4. Hostname-Finder implementieren.
5. lokale Wildcard-Domain für `*.lazytown.test` konfigurieren.
6. Tests für gültige, unbekannte und deaktivierte Subdomains schreiben.

### Phase 3: Zugriffsschutz

1. Tenant-Aktivierung vor Auth-/Fachrouten einführen.
2. Membership im aktiven Tenant prüfen.
3. Spatie-Permission-Scope zuverlässig setzen.
4. bestehende Policies gegen den aktiven Tenant testen.
5. Pfadbasierte Team-Routen auf Subdomain-Routen migrieren.

### Phase 4: Feature-System

1. Feature- und Pivot-Migration erstellen.
2. Models und Beziehungen ergänzen.
3. Feature-Registry und Service einführen.
4. Middleware sowie Gates/Policies ergänzen.
5. Verwaltungsbereich für Feature-Aktivierung bauen.
6. Tests für aktiviert, deaktiviert, abgelaufen und unberechtigt ergänzen.

### Phase 5: Inertia/Vue

1. Shared Props für Tenant und Features ergänzen.
2. `hasFeature`- und `can`-Helfer zentralisieren.
3. Navigation und Layouts featureabhängig machen.
4. direkte deaktivierte Aufrufe und Fehlerseiten behandeln.
5. TypeScript-Typen für Shared Props aktualisieren.

### Phase 6: Datenmigration und Bereinigung

1. Bestehende Teams als Tenants behandeln.
2. Daten aller tenantbezogenen Tabellen auf vollständige Vereinszuordnung prüfen.
3. globale Rollen nicht mehr für Vereinszugriff verwenden.
4. alte URL-Parameter und Legacy-Middleware entfernen, sobald alle Routen migriert sind.
5. Cache-, Job-, Notification-, Storage- und Event-Kontext auf Tenant-Isolation prüfen.

## Sicherheits- und Skalierungsregeln

- Frontend-Sichtbarkeit ist niemals eine Autorisierung.
- Jede tenantbezogene Query muss den aktiven Tenant berücksichtigen.
- Cache-Keys müssen die Tenant-ID enthalten.
- Queued Jobs, Notifications und Events müssen den Tenant-Kontext mitführen.
- Dateipfade und Exporte müssen tenantbezogen isoliert werden.
- Route Model Binding darf keinen Tenant aus einem anderen Verein auflösen.
- Eindeutigkeiten müssen fachlich geprüft werden: global oder pro Tenant.
- Tenant-Wechsel darf nicht nur über `current_team_id` erfolgen, wenn die URL den Tenant festlegt.
- Logging und Monitoring sollten die aktive Tenant-ID enthalten.
- Tests müssen insbesondere Cross-Tenant-Zugriffe abdecken.

## Teststrategie

Mindestens folgende Testgruppen werden benötigt:

1. Hostname löst den richtigen Verein auf.
2. unbekannte oder deaktivierte Subdomain wird abgewiesen.
3. authentifizierter Benutzer ohne Membership erhält keinen Zugriff.
4. aktives Mitglied kann den eigenen Tenant aufrufen.
5. Mitglied von Verein A kann keine Daten von Verein B lesen oder verändern.
6. Spatie-Permissions werden im richtigen Team-Scope ausgewertet.
7. aktivierte Features erlauben ihre Routen und Aktionen.
8. deaktivierte Features blockieren Backend-Routen auch bei direktem Request.
9. Vue-/Inertia-Props enthalten nur den aktiven Tenant und dessen Features.
10. Jobs, Cache und Notifications behalten den Tenant-Kontext.

## Fazit

Der Umbau ist auf der bestehenden Codebasis gut möglich. Die sauberste Lösung ist zunächst kein neues paralleles `Tenant`-Modell, sondern die kontrollierte Weiterentwicklung von `Team` zum Tenant. Die größten Architekturarbeiten liegen in der Umstellung vom frei wechselbaren `current_team` und pfadbasierten Team-Routen auf einen durch die Subdomain festgelegten Tenant-Kontext sowie in der Ergänzung eines zentralen Feature-Systems.

Die Implementierung sollte erst nach dieser Modellentscheidung beginnen. Danach können Tenant-Auflösung, Membership-Schutz und Feature-Prüfungen schrittweise eingeführt werden, ohne die bestehende Team- und Inertia-Struktur auf einmal zu ersetzen.
