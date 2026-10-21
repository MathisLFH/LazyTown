# Backend

Das Backend basiert auf Laravel 13 und nutzt klassische Laravel-Web-Routen, Controller, Form Requests, Eloquent und Inertia. Es gibt keine separate Repository-Schicht, REST-Routen-Datei oder installierbare Modulstruktur.

## Request-Lifecycle

1. `bootstrap/app.php` registriert [routes/web.php](../routes/web.php), [routes/console.php](../routes/console.php), den Health-Endpunkt `/up` sowie globale Middleware und Exception-Formatierung.
2. Der Web-Stack führt unter anderem `ResolveTenantFromSubdomain`, `HandleAppearance`, `HandleInertiaRequests`, `SetTeamUrlDefaults`, `SetPermissionTeam` und `EnsureInitialPasswordChanged` aus.
3. Routen ergänzen geschützte Middleware wie `auth`, `verified`, `club.access`, `role:verwaltung` oder `EnsureTeamMembership`.
4. Laravel bindet Route-Modelle. Form Requests prüfen Eingaben und gegebenenfalls die fachliche Autorisierung.
5. Controller delegieren wiederverwendbare Vorgänge an Actions oder Services und verwenden Eloquent für Datenzugriff.
6. Seitenaktionen geben Inertia-Seiten und Props zurück; erfolgreiche Änderungen leiten meistens zurück oder zu einer benannten Route weiter. Such- und CSV-Vorschauen geben JSON zurück.

Der Ablauf ist im [Architekturdiagramm](./architecture.md) und die konkreten Endpunkte in [api.md](./api.md) dargestellt.

## Routing und Schichten

| Ebene | Ort | Zweck |
|---|---|---|
| Routing | `routes/web.php`, `routes/settings.php` | Web- und Inertia-Routen; Settings werden aus `web.php` eingebunden |
| Middleware | `app/Http/Middleware/` | Auth-/Rollen-/Tenant-/Inertia-Kontext |
| Validierung | `app/Http/Requests/` und Controller | Form Requests mit Regeln und `authorize()`; einzelne einfache Endpunkte validieren inline |
| Controller | `app/Http/Controllers/` | HTTP-Orchestrierung, Inertia-Props, Redirects und einzelne JSON-Antworten |
| Actions | `app/Actions/` | Abgegrenzte Schreibvorgänge, z. B. Team anlegen und Stripe-Webhooks verarbeiten |
| Services | `app/Services/` | Geteilte Fachlogik wie CSV-Parsing, Permission-Synchronisierung und Stripe-Checkout |
| Modelle | `app/Models/` | Eloquent-Datenmodell, Beziehungen, Casts und Suchbarkeit |
| Policy | `app/Policies/` | Modellbezogene Team-Autorisierung |

`app/Repositories/`, `app/Http/Resources/` und `routes/api.php` sind nicht vorhanden. Eloquent wird direkt in Services und Controllern verwendet. Weitere Details nach Fachbereich stehen in [modules/README.md](./modules/README.md).

## Validierung, Fehler und Antworten

- Eingabevalidierung liegt überwiegend in `FormRequest`-Klassen. Dazu gehören Request-Autorisierung und Datenregeln.
- Fehlerhafte Formulare verwenden Laravel-Validierungsfehler und Inertia-Redirect-Verhalten.
- Nicht erlaubte Operationen nutzen unter anderem `Gate::authorize`, `abort(403/404)` und Form-Request-Autorisierung.
- `bootstrap/app.php` verlangt JSON-Fehlerantworten für `api/*` oder Requests mit `expectsJson()`. Es existiert derzeit kein eigenes `api/*`-Routing.
- Stripe-Webhookfehler werden mit HTTP 400 zurückgegeben und als Warnung geloggt; akzeptierte Webhooks antworten mit 204.
- Erfolgs-Toast-Daten werden an vielen Schreibvorgängen über `Inertia::flash('toast', ...)` gesetzt.

## Authentifizierung, Mandanten und Berechtigungen

Der Standard-Guard `web` ist sessionbasiert und verwendet Eloquent-`User`. Laravel Fortify und Laravel Passkeys stellen die Auth-Endpunkte bereit. Tenant-Erkennung, globale User-Rollen und Team-Permissions sind voneinander getrennte Prüfungen; siehe [auth.md](./auth.md).

## Queues, Notifications und Scheduler

- `QUEUE_CONNECTION=database` ist in `.env.example` gesetzt.
- `TeamInvitation` ist eine `ShouldQueue`-Notification und verwendet Mail- sowie Database-Kanäle. Ein Queue-Worker ist nicht im Deploy-Workflow gestartet.
- Planänderungen versenden Database-Notifications synchron über `ScheduleUpdateNotifier`.
- [routes/console.php](../routes/console.php) registriert eine tägliche Bereinigung abgelaufener Teameinladungen. Ein Scheduler-Daemon beziehungsweise System-Cron wird durch den Code nicht eingerichtet.
- Es gibt keine eigenen `app/Jobs/`, `app/Events/` oder `app/Listeners/`-Verzeichnisse.

## Konfiguration und Pakete

Wichtige Konfigurationsdateien: [bootstrap/app.php](../bootstrap/app.php), [config/auth.php](../config/auth.php), [config/fortify.php](../config/fortify.php), [config/database.php](../config/database.php), [config/permission.php](../config/permission.php), [config/scout.php](../config/scout.php), [config/services.php](../config/services.php) und [config/notifications.php](../config/notifications.php).

| Paket | Verwendung im Projekt |
|---|---|
| `laravel/framework` | HTTP, Eloquent, Auth, Queue, Scheduler, Notifications |
| `inertiajs/inertia-laravel` | Serverseitige Inertia-Seiten und Props |
| `laravel/fortify` | Login, Registrierung, Passwort-Reset, E-Mail-Verifikation und 2FA |
| `laravel/passkeys` (transitiv installiert) | WebAuthn-Passkey-Authentifizierung |
| `spatie/laravel-permission` | Teambezogene Rollen und Berechtigungen |
| `laravel/scout` | Model-Suche mit Database-Engine als Standard |
| `laravel/wayfinder` | Typisierte TS-Hilfen für Controller-/Routenaufrufe |
| `stripe/stripe-php` | Checkout und Signaturprüfung für Stripe-Test-Webhooks |
| `laravel/chisel` | Toolkit für Skripte/Codebereinigung aus dem Laravel-Ökosystem |
| `laravel/tinker` | Interaktive Laravel-Konsole |
| `fakerphp/faker` | Generierung von Testdaten |
| `larastan/larastan` | PHPStan-Erweiterung für Laravel |
| `pestphp/pest`, `pestphp/pest-plugin-laravel` | PHP-Tests mit Laravel-Integration |
| `mockery/mockery` | Test-Doubles und Mocking |
| `laravel/pint` | PHP-Codeformatierung |
| `nunomaduro/collision` | Lesbare CLI-Fehlerdarstellung |
| `laravel/boost` | Laravel-Entwicklungs-/Agent-Werkzeuge |
| `laravel/pail` | CLI-Loganzeige |
| `laravel/pao` | Agent-optimierte Test-/Analyseausgabe |
| `laravel/sail` | Laravel-Docker-Entwicklungsdateien; das Projekt verwendet lokal direkt Compose für PostgreSQL |

Composer-Abhängigkeiten in [composer.json](../composer.json) umfassen zusätzlich ihre dev-/runtime-Zuordnung. Die installierten Versionsstände werden mit `composer show --direct` geprüft.
