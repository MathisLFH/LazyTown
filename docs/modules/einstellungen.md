# Fachbereich: Einstellungen

Dieser Fachbereich umfasst Profilpflege, Datenexport, Sicherheitsansicht, Passwortänderung, Darstellungsoptionen und Benachrichtigungspräferenzen. Fortify liefert die zugrundeliegenden Passwort-, Passkey- und 2FA-Endpunkte; diese Seite bündelt projektspezifische Einstellungsseiten.

## Struktur und wichtige Dateien

| Datei | Aufgabe |
|---|---|
| [ProfileController.php](../../app/Http/Controllers/Settings/ProfileController.php) | Rendert, aktualisiert und löscht das Benutzerprofil. |
| [DataExportController.php](../../app/Http/Controllers/Settings/DataExportController.php) | Liefert einen JSON-Download der freigegebenen Profildaten und Teamnamen. |
| [SecurityController.php](../../app/Http/Controllers/Settings/SecurityController.php) | Liefert Passkey-/2FA-Status und aktualisiert das Passwort. |
| [NotificationSettingsController.php](../../app/Http/Controllers/Settings/NotificationSettingsController.php) | Lädt und speichert Kategorien der Benachrichtigungseinstellungen. |
| [ProfileUpdateRequest.php](../../app/Http/Requests/Settings/ProfileUpdateRequest.php) | Validiert Profiländerungen und verbietet Rollenfelder. |
| [PasswordUpdateRequest.php](../../app/Http/Requests/Settings/PasswordUpdateRequest.php) | Validiert aktuelles und neues Passwort. |
| [TwoFactorAuthenticationRequest.php](../../app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php) | Prüft den Fortify-Zustand der Sicherheitsseite. |
| [User.php](../../app/Models/User.php) | Speichert Profil-, Rollen-, 2FA- und Notification-Einstellungen. |
| [config/notifications.php](../../config/notifications.php) | Definiert die verfügbaren Kategorien und Beschreibungstexte. |

## Routen

| Methode | URI | Controller@Action | Middleware |
|---|---|---|---|
| GET | `/profil` | Inertia-Seite `Profil` | web, auth |
| GET | `/settings` | Redirect auf `/settings/profile` | web, auth |
| GET / PATCH | `/settings/profile` | `ProfileController@edit/update` | web, auth |
| DELETE | `/settings/profile` | `ProfileController@destroy` | web, auth, verified |
| GET | `/settings/profile/export` | `DataExportController@__invoke` | web, auth |
| GET / PATCH | `/settings/notifications` | `NotificationSettingsController@edit/update` | web, auth |
| GET | `/settings/security` | `SecurityController@edit` | web, auth, verified, RequirePassword, club.access |
| PUT | `/settings/password` | `SecurityController@update` | web, auth, verified, throttle:6,1, club.access |
| GET | `/settings/appearance` | Inertia-Seite `settings/Appearance` | web, auth, verified, club.access |

Fortify-Passkey- und 2FA-Aktionen sind im [Authentifizierungsfachbereich](./authentifizierung.md) aufgeführt.

## Daten, Abhängigkeiten und Frontend

Die Profilseite ändert nur freigegebene Profilfelder; Rollen sind dort ausdrücklich verboten. Der Datenexport streamt `profile` und `teams` als `application/json`. Benachrichtigungseinstellungen werden als JSON im User gespeichert; unbekannte Einstellungen sind keine neue Datenbanktabelle.

Vue-Seiten: [Profil.vue](../../resources/js/pages/Profil.vue), `pages/settings/Profile.vue`, `Security.vue`, `Notifications.vue` und `Appearance.vue`. Sicherheit verwendet Fortify- und Passkey-Funktionalität. Benachrichtigungspräferenzen wirken auf die Dispatcher-Logik des [Benachrichtigungsbereichs](./benachrichtigungen.md).

## Tests

- [ProfileUpdateTest.php](../../tests/Feature/Settings/ProfileUpdateTest.php)
- [SecurityTest.php](../../tests/Feature/Settings/SecurityTest.php)
- [NotificationSettingsTest.php](../../tests/Feature/Settings/NotificationSettingsTest.php)

Für Einstellungen existieren keine eigenen Jobs, Events/Listener, API Resources oder Repository-Klassen.

