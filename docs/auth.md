# Authentifizierung und Autorisierung

Die Anwendung authentifiziert Benutzer mit Laravel Fortify über den sessionbasierten `web`-Guard. Passkeys und Zwei-Faktor-Authentifizierung ergänzen die Anmeldung; Tenant- und Teamrechte werden unabhängig davon serverseitig geprüft.

## Anmeldung und Kontoflüsse

- `config/auth.php` legt `web` mit Session-Treiber und Eloquent-`User`-Provider fest.
- [FortifyServiceProvider.php](../app/Providers/FortifyServiceProvider.php) bindet Login-, Registrierungs-, 2FA-, Passkey- und Verifizierungsantworten an projektspezifische Klassen und rendert Inertia-Seiten.
- `config/fortify.php` aktiviert Registrierung, Passwort-Reset, E-Mail-Verifizierung, bestätigte 2FA und Passkeys.
- Passkey-Relying-Party-ID und erlaubte Origin werden von `APP_URL` abgeleitet; der User-Handle-Secret fällt auf `APP_KEY` zurück, sofern `PASSKEYS_USER_HANDLE_SECRET` nicht gesetzt ist.
- Ein administrativ angelegtes Mitglied kann `must_change_password=true` erhalten. `EnsureInitialPasswordChanged` erzwingt dann Profilergänzung und Passwortwechsel.
- OAuth-/Social-Login ist nicht implementiert: Es gibt keine Socialite-Abhängigkeit oder entsprechende Routen.

## Zwei unterschiedliche Rollensysteme

| System | Speicherort | Beispiele | Anwendung |
|---|---|---|---|
| Globale Anwendungsrolle | `users.roles` (JSON), `users.active_role` | `spieler`, `trainer`, `verwaltung` | `User::hasRole()`, `role`-Middleware und Verwaltungsrouten |
| Teammitgliedschaftsrolle | `team_members.role` | `owner`, `admin`, `member` | Teamverwaltung und Eigner-/Trainer-/Spielerbeziehung |
| Paketrollen/Berechtigungen | `roles`, `permissions` und Pivot-Tabellen | `team:update`, `member:add`, `invitation:create` | Spatie Permission im aktuellen Teamkontext |

Die Benennung ist historisch gewachsen: Das `ClubRole`-Modell zeigt auf die Spatie-Tabelle `roles`, während Teamrollen als Enum am `team_members`-Pivot gespeichert sind. Teamrechte werden vom `TeamPermissionSynchronizer` mit den Spatie-Rollen abgeglichen. Globale Rollen ersetzen keine Tenant-/Team-Prüfung.

## Tenant- und Teamzugriff

- [ResolveTenantFromSubdomain.php](../app/Http/Middleware/ResolveTenantFromSubdomain.php) vergleicht den Host mit der aus `APP_URL` abgeleiteten Root-Domain und lädt den passenden `Tenant`.
- `EnsureClubAccess` verlangt mindestens eine aktive Mitgliedschaft in einem nicht-persönlichen Team; Trainer ohne Verein werden zum Onboarding geleitet.
- `EnsureTeamMembership` schützt Teamrouten zusätzlich vor Zugriff auf fremde Teams.
- `SetPermissionTeam` setzt den aktuellen Spatie-Team-Scope; `SetTeamUrlDefaults` ergänzt Team-URL-Parameter.
- `EnsureUserRole` rendert bei fehlender globaler Rolle eine Inertia-403-Seite.
- [TeamPolicy.php](../app/Policies/TeamPolicy.php) autorisiert teambezogene Anzeige, Änderung, Löschung, Einladungen und Mitgliedsverwaltung.
- `verified` schützt ausgewählte Einstellungen und Verwaltungsrouten; die Sicherheitsseite verlangt zusätzlich Passwortbestätigung.

## Begrenzung von Anmeldeversuchen

| Vorgang | Konfiguration |
|---|---|
| Login | 5 pro Minute je normalisierter E-Mail/IP-Kombination |
| 2FA-Challenge | 5 pro Minute je Login-Session |
| Passkey-Flows | 10 pro Minute je Credential-/Session-ID und IP |
| Suche | 60 pro Minute je Benutzer-ID und IP |
| Passwortänderung | `throttle:6,1` auf der Route |

Die Auth-Limits stehen in [FortifyServiceProvider.php](../app/Providers/FortifyServiceProvider.php); die Such- und Passwortlimits sind in [AppServiceProvider.php](../app/Providers/AppServiceProvider.php) beziehungsweise [routes/settings.php](../routes/settings.php) konfiguriert.

## Speicherung und weitere Details

Benutzerpasswörter werden als gehashter Cast gespeichert. 2FA-Geheimnis und Recovery-Codes liegen in den User-Feldern; Passkey-Credentials werden in `passkeys` gespeichert. Die Feature-Routen und Tests stehen unter [modules/authentifizierung.md](./modules/authentifizierung.md); Tabellen beschreibt [database.md](./database.md).

