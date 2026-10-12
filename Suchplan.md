# Suchplan für LazyTown

## 1. Ziel und Umfang

Das bereits vorhandene Suchfeld in der globalen Navigation soll bei der Eingabe
sofort passende Vorschläge anzeigen. Ein Vorschlag führt direkt zu der
zugehörigen Seite oder zum Datensatz.

Die Suche soll schrittweise wachsen:

- **Startumfang:** User, Teams und bereits vorhandene Navigations-/Settings-Seiten.
- **Nächster Domänenumfang:** Hallenorte (`Gym`) und später Trainings bzw. Spiele.
- **Später möglich:** Nachrichten, weitere Verwaltungsobjekte und eine
  vollständige Suchergebnisseite.

Das Suchfeld ist eine Vorschlagssuche, keine separate Google-ähnliche
Volltextseite. Bei leerer Eingabe werden keine Datenbankabfragen ausgeführt.

## 2. Bestand und wichtige Randbedingungen

- Das Eingabefeld befindet sich in
  [`resources/js/components/Navbar.vue`](resources/js/components/Navbar.vue).
  Es besitzt aktuell nur `v-model="searchQuery"` und erzeugt noch keine
  Anfrage.
- Die Anwendung verwendet Laravel 13, Inertia 3, Vue 3, Wayfinder und
  `spatie/laravel-permission`.
- Laravel Scout ist **noch nicht** in `composer.json` installiert.
- [`App\Models\User`](app/Models/User.php), [`App\Models\Team`](app/Models/Team.php)
  und [`App\Models\Gym`](app/Models/Gym.php) sind bereits vorhanden.
- `Team` und `User` besitzen `tenant_id`. Die Tabelle `gyms` besitzt nach der
  aktuellen Migration dagegen keinen `tenant_id`, obwohl `trainings` einen
  `gym_id` referenziert. Hallen dürfen erst in die Suche aufgenommen werden,
  wenn diese Mandantentrennung geklärt und technisch abgesichert ist.
- Die Navigation ist nur für eingeloggte User mit Club-Mitgliedschaft sichtbar.
  Die serverseitige Suche muss trotzdem eigene Authentifizierung,
  Mandantenprüfung und Berechtigungsprüfung durchführen.

## 3. Empfohlene technische Entscheidung

### 3.1 Suchkern: Laravel Scout mit Database Engine

Für die erste Implementierung soll **Laravel Scout** verwendet werden:

```text
composer require laravel/scout
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

Scout bietet in der aktuellen Laravel-13-Dokumentation eine eingebaute
`database`-Engine. Sie verwendet die bestehenden Datenbankdaten, ohne
Meilisearch, Algolia oder einen weiteren Dienst zu benötigen. Das passt zum
kleinen initialen Umfang und vermeidet eine zusätzliche Infrastruktur.

Vorgehen:

1. `Searchable` nur an Modellen aktivieren, die tatsächlich vorgeschlagen
   werden sollen.
2. Pro Modell `toSearchableArray()` auf die erlaubten Suchfelder begrenzen.
3. Scout-Suchergebnisse nicht direkt als API-Ausgabe verwenden, sondern über
   einen gemeinsamen Adapter in ein einheitliches Vorschlagsformat überführen.
4. Tenant- und Berechtigungsfilter immer zusätzlich in Laravel erzwingen.
   Ein Suchindex ist keine Autorisierungsgrenze.

Die Database Engine ist zunächst ausreichend. Bei stark wachsendem Datenbestand
oder Anforderungen wie Tippfehlertoleranz, Facetten oder sehr schnellen
Volltextabfragen kann derselbe Scout-Vertrag später auf Meilisearch,
Typesense oder Algolia umgestellt werden. Diese Umstellung ist ausdrücklich
kein Teil der ersten Umsetzung.

### 3.2 Kein zusätzliches Frontend-Suchpaket in der ersten Stufe

Für die Vorschlagsliste ist kein weiteres Autocomplete-Paket erforderlich:

- Das bestehende Input-Element und die vorhandenen Dropdown-/Popover-UI-Muster
  wiederverwenden.
- Falls eine vollständig zugängliche Combobox benötigt wird, bevorzugt das
  bereits installierte `reka-ui` statt einer neuen Abhängigkeit.
- Keine eigene globale State-Library nur für die Suche einführen.

## 4. Einheitliches Suchmodell

Alle Provider liefern intern denselben Datentyp. Dadurch können neue
Suchbereiche hinzugefügt werden, ohne die Navbar oder das Antwortformat
umzubauen.

Beispiel für die interne PHP-/JSON-Struktur:

```ts
type SearchSuggestion = {
    type: 'user' | 'team' | 'gym' | 'setting' | 'training' | 'match' | 'message';
    title: string;
    subtitle?: string;
    url: string;
    icon?: string;
    score?: number;
};

type SearchGroup = {
    type: SearchSuggestion['type'];
    label: string;
    items: SearchSuggestion[];
};

type SearchResponse = {
    query: string;
    groups: SearchGroup[];
};
```

`score` ist intern und muss nicht an das Frontend ausgegeben werden. Die
endgültige Antwort soll nur sichtbare und autorisierte Vorschläge enthalten.

## 5. Backend-Architektur

### 5.1 Route und Controller

Eine geschützte, benannte GET-Route soll als JSON-Endpunkt dienen:

```text
GET /suche?q=<query>&limit=<limit>
```

Vorgeschlagener Name: `search.suggestions`.

Der Controller soll:

1. `q` validieren und trimmen:
   - String,
   - maximal 100 Zeichen,
   - mindestens zwei Zeichen für eine Suche,
   - bei leerem oder zu kurzem Text `groups: []` zurückgeben.
2. Ein maximales Gesamtergebnis erzwingen, z. B. 8 oder 10 Vorschläge.
3. Einen zentralen `SearchService` aufrufen.
4. Das standardisierte Format als JSON zurückgeben.
5. Über einen Rate Limiter gegen missbräuchliche Abfragen geschützt werden.

Die Route soll nicht als öffentliche Inertia-Seite gebaut werden. Die
Navigationssuche braucht eine kleine JSON-Antwort; eine spätere vollständige
Suchseite kann denselben Service wiederverwenden.

### 5.2 Provider-Vertrag als Erweiterungsvorlage

Statt alle Modelle im Controller zu vermischen, soll ein kleiner Provider-
Vertrag eingeführt werden, beispielsweise:

```php
interface SearchProvider
{
    /** @return Collection<int, SearchSuggestion> */
    public function search(string $query, SearchContext $context, int $limit): Collection;
}
```

`SearchContext` enthält mindestens:

- aktuellen User,
- aktuellen Tenant,
- aktuelles Team,
- verfügbare Rollen/Berechtigungen.

Der `SearchService` registriert die Provider in fester Reihenfolge, sammelt
die Ergebnisse, entfernt Duplikate, wendet das GesamtlLimit an und gruppiert
nach `type`. Ein neuer Suchbereich benötigt dann nur:

1. Provider-Klasse,
2. autorisierte Query,
3. Mapping zu `SearchSuggestion`,
4. Registrierung im Service,
5. Provider-Test.

Falls die Codebasis zu diesem Zeitpunkt bewusst einfacher bleiben soll, kann
die erste User-/Team-Implementierung zunächst in einem Service liegen. Der
Provider-Vertrag sollte spätestens vor der Hallen-Integration eingeführt
werden, damit keine weitere `if`-Kette in Controller oder Navbar entsteht.

### 5.3 Autorisierung und Mandantentrennung

Jeder Provider muss seine Query einschränken, bevor Ergebnisse geladen werden:

- **User:** nur User desselben `tenant_id`, sofern die bestehende
  Datenschutz-/Rollenregel das zulässt; E-Mail nur suchen, wenn das fachlich
  gewollt ist, und nicht als sichtbaren Untertitel verwenden, wenn sie nicht
  angezeigt werden soll.
- **Team:** nur Teams des aktuellen Tenants, denen der User mindestens
  entsprechend der bestehenden Club-Regeln begegnen darf.
- **Gym:** nur Hallen, die über eine sichere Tenant-Beziehung dem aktuellen
  Tenant zugeordnet sind.
- **Settings:** keine Datenbank-Suche; nur eine statische,
  rollenabhängige Registry aus bereits existierenden benannten Routen.

Keine globale `User::search($query)->get()`-Abfrage verwenden. Die
Autorisierung darf auch nicht erst nach dem Laden der Treffer als PHP-Filter
erfolgen, weil dabei bereits geschützte Datensätze in den Prozess gelangen.

### 5.4 Suchfelder und Ergebnisdarstellung

Empfohlene erste Felder:

| Typ | Suchfelder | Vorschlag zeigt | Ziel |
|---|---|---|---|
| User | `name`, `first_name`, `last_name`, optional `public_id` | Name, optional Rolle/Team | Profil- oder erlaubte Mitgliederseite |
| Team | `name`, `slug` | Teamname | Team-/Teamverwaltungsseite |
| Setting | statischer Titel, Beschreibung, Suchbegriffe | Titel, Bereich „Einstellungen“ | Benannte Settings-Route |
| Gym | `name`, später Adresse/Ort | Hallenname, Ort | Hallenplan bzw. Hallendetail |

Passwörter, Recovery-Codes, interne IDs und andere geschützte Attribute
gehören niemals in den Suchindex oder die Antwort.

## 6. Hallenorte als vorbereitete, aber sichere Erweiterung

### 6.1 Datenmodell zuerst korrigieren

Vor der Hallensuche muss geklärt werden, ob eine Halle tenantweit oder global
ist. Für die vorhandene Club-/Tenant-Architektur ist die bevorzugte Variante:

```text
gyms:
  id
  tenant_id  (foreign key, cascade on delete)
  name
  address/city/... (nur wenn fachlich benötigt)
  timestamps
```

Dazu gehören:

- Migration für `gyms.tenant_id`,
- Anpassung von `Gym::$fillable` bzw. der Attribute-Konvention,
- Beziehung `Gym::tenant()`,
- eindeutige/geeignete Indizes, mindestens `(tenant_id, name)`,
- Factory-/Fixture-Anpassung,
- Tests für tenantübergreifenden Zugriff.

Die existierende Migration nicht nachträglich umschreiben, sondern eine neue
Migration erstellen. Bestehende Trainings müssen nach der Migration weiterhin
auf ihre Halle zeigen; verwaiste oder globale Hallen sind vorab fachlich zu
behandeln.

### 6.2 Gym-Provider als Vorlage

Der spätere `GymSearchProvider` soll:

1. den Tenant aus `SearchContext` verwenden,
2. über `Gym::query()->where('tenant_id', ...)` einschränken,
3. den Namen und später Ortsfelder durchsuchen,
4. eine erlaubte Zielroute über Wayfinder/benannte Route liefern,
5. mit einem Feature-Test beweisen, dass keine fremde Halle erscheint.

Bis es eine Hallendetailseite gibt, soll der Vorschlag auf `hallenplan` mit
einem Query-Parameter oder auf die später definierte Hallenroute zeigen. Die
Zielroute muss als eigener Entscheidungspunkt umgesetzt werden, nicht als
hart codierte URL im Vue-Template.

## 7. Settings-Suche

Settings sind keine Eloquent-Suchobjekte. Dafür soll eine kleine Registry
verwendet werden, zum Beispiel mit Einträgen für:

- Profil,
- Sicherheit,
- Erscheinungsbild,
- Teams,
- Teamverwaltung,
- Bezahlung, nur für die dafür berechtigten Rollen.

Jeder Eintrag enthält Titel, Beschreibung, Suchbegriffe, benannte Route und
eine Autorisierungsfunktion. Der Provider filtert die Registry anhand von
User, Tenant, Club-Zugriff und Rolle. So werden keine versteckten
Verwaltungsseiten als Vorschläge verraten.

Neue Settings werden dadurch mit einem einzelnen Registry-Eintrag
auffindbar; ein weiterer Datenbankindex ist nicht nötig.

## 8. Frontend-Verhalten in `Navbar.vue`

### 8.1 Eingabe und Requests

Die bestehende Eingabe soll möglichst wenig verändert werden:

- `searchQuery` beibehalten.
- Ab zwei Zeichen mit 200–300 ms Debounce den benannten JSON-Endpunkt
  aufrufen.
- Vorherige Anfrage bei neuer Eingabe abbrechen oder deren Ergebnis anhand
  einer Request-ID verwerfen.
- Bei leerer/zu kurzer Eingabe Liste schließen und Antwortzustand löschen.
- Lade-, Fehler- und „keine Treffer“-Zustände getrennt darstellen.
- Kein Request bei jedem einzelnen Tastendruck ohne Debounce.

Inertia v3s eingebauten XHR-/HTTP-Weg oder den im Projekt üblichen Client
verwenden; Axios nicht neu einführen.

### 8.2 Zugängliche Vorschlagsliste

- Input erhält `role="combobox"`, `aria-expanded`,
  `aria-controls` und einen stabilen Listbox-Identifier.
- Ergebnisse werden nach Typ gruppiert und mit verständlichen deutschen
  Labels versehen.
- Pfeil hoch/runter wählt, Enter öffnet, Escape schließt.
- Maus-/Touch-Auswahl öffnet exakt `url`.
- Fokus bleibt bis zur Navigation sinnvoll am Input.
- Die Liste liegt in einem bestehenden Dropdown-/Popover-Muster und erhält
  keine separate globale Overlay-Infrastruktur.

Die Vorschlagsdaten dürfen nicht direkt als beliebiges HTML gerendert werden;
Titel und Untertitel bleiben Textwerte.

## 9. Ranking, Limits und Verhalten

Für die erste Version:

- exakte Präfix-/Namensübereinstimmung vor Teilübereinstimmung,
- `name` vor weniger wichtigen Zusatzfeldern,
- Settings und direkte Navigation vor umfangreichen Domänentreffern,
- maximal 3–5 Treffer je Gruppe,
- maximal 8–10 Treffer insgesamt,
- stabile Sortierung bei gleicher Relevanz,
- Eingabe normalisieren (Trimmen, Groß-/Kleinschreibung über Engine/DB),
- keine fuzzy Suche und keine semantische Suche im MVP.

Wenn reale Nutzung zeigt, dass Tippfehlertoleranz erforderlich ist, wird als
separater Schritt Meilisearch über Laravel Scout bewertet. Dabei sind
Indexierung, Queue, lokale Entwicklung, Secrets, Reindexierung und
Produktionsbetrieb ausdrücklich mitzudenken; die externe Suchmaschine darf
nicht stillschweigend Voraussetzung für lokale Entwicklung werden.

## 10. Vorgeschlagene Implementierungsreihenfolge

### Phase 0 – Vorbereitung

1. Bestehende Routen, Sichtbarkeit und Tenant-Regeln final bestätigen.
2. Zielroute für User-/Teamvorschläge festlegen.
3. Entscheiden, ob E-Mail und `public_id` suchbar sein sollen.
4. Scout als einzige neue Composer-Abhängigkeit freigeben.

### Phase 1 – Suchinfrastruktur

1. Scout installieren und konfigurieren.
2. `SearchSuggestion`, `SearchContext` und Provider-Vertrag anlegen.
3. `SearchService` und JSON-Controller erstellen.
4. Benannte, geschützte Route mit Rate Limit und Request-Validierung
   registrieren.
5. Standardformat und Ergebnislimits testen.

### Phase 2 – Erste Treffer

1. User-Provider mit Tenant- und Berechtigungsfilter.
2. Team-Provider mit Tenant- und vorhandenen Teamzugriffsregeln.
3. Settings-Registry und Settings-Provider.
4. Wayfinder für die neue Route generieren.
5. Bestehendes Suchfeld in `Navbar.vue` mit Debounce, Zuständen und
   Tastaturbedienung verbinden.

### Phase 3 – Hallenorte

1. Tenant-Zuordnung für `gyms` migrieren.
2. Gym-Modell, Factory und Beziehungen ergänzen.
3. Gym-Provider nach demselben Vertrag hinzufügen.
4. Hallen-Zielroute oder Hallenplan-Deep-Link festlegen.
5. Mandanten- und Berechtigungstests ausführen.

### Phase 4 – Ausbau

1. Trainings, Spiele und Nachrichten nur nach fachlicher Freigabe ergänzen.
2. Bei Bedarf vollständige Suchergebnisseite mit Pagination bauen.
3. Erst bei messbarem Bedarf Meilisearch/Typesense/Algolia evaluieren.
4. Suchmetriken ohne Suchbegriffe oder personenbezogene Inhalte erfassen,
   etwa Trefferzahl, Latenz und Provider.

## 11. Tests und Abnahmekriterien

### Backend-/Feature-Tests

- unauthentifizierte Requests werden abgewiesen;
- zu kurze, zu lange und fehlende `q`-Werte verhalten sich definiert;
- User-Suche liefert nur autorisierte User des aktuellen Tenants;
- Team-Suche liefert keine fremden oder nicht sichtbaren Teams;
- Settings erscheinen abhängig von Rolle und Club-Zugriff;
- leere Treffer liefern ein gültiges leeres `groups`-Format;
- Ergebnislimits werden je Gruppe und insgesamt eingehalten;
- Hallen-Suche zeigt keine Halle eines anderen Tenants;
- jedes Ergebnis enthält eine erlaubte, benannte Zielroute.

### Frontend-/Komponententests

- Debounce verhindert unnötige Requests;
- Lade-, Fehler- und Leerzustand werden angezeigt;
- alte Antworten überschreiben keine neuere Eingabe;
- Keyboard-Navigation und Escape funktionieren;
- Auswahl navigiert zum Vorschlag;
- bei Abmeldung/fehlender Club-Mitgliedschaft bleibt die vorhandene
  Navbar-Sichtbarkeit unverändert.

### Abnahme

Die erste Ausbaustufe ist fertig, wenn User, Teams und erlaubte Settings im
bestehenden Suchfeld als gruppierte Vorschläge erscheinen, direkt navigieren,
keine fremden Tenant-Daten preisgeben und ohne zusätzliche Suchinfrastruktur
lokal funktionieren. Hallenorte gelten erst nach der Datenmodell- und
Mandantentrennung als aktiviert.

## 12. Bewusst nicht im ersten Schritt

- kein globaler Suchindex mit allen Tabellen;
- keine Suche in Passwort-, Sicherheits- oder privaten Profildaten;
- keine hardcodierten URLs in der Vue-Komponente;
- keine externe Meilisearch-/Algolia-Pflicht;
- keine Änderungen an bestehenden Hallen-/Trainingsdaten ohne Migration;
- keine separate Suchseite, solange Vorschläge im Header ausreichen;
- keine Änderungen an [`.gitattributes`](.gitattributes).

## Quellen

- [Laravel Scout 13.x – Database Engine und Suchmodelle](https://laravel.com/docs/13.x/scout)
- [Laravel 13.x – Full-Text-/Datenbankabfragen](https://laravel.com/docs/13.x/queries#full-text-where-clauses)
- [Meilisearch – lokale Installation und Betriebsoptionen](https://www.meilisearch.com/docs/resources/self_hosting/getting_started/install_locally)
