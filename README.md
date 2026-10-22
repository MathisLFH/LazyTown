# LazyTown

LazyTown ist eine Laravel-Webanwendung zur Verwaltung von Vereinen, Mannschaften und Sportplänen. Die Anwendung verbindet ein Laravel-Backend mit Vue-Seiten über Inertia; PostgreSQL speichert Vereins-, Mitglieder- und Planungsdaten.

> **Dokumentationsstand:** Diese Beschreibung folgt dem aktuellen Code und den Konfigurationsdateien. Ältere Planungsnotizen im Repository können davon abweichen.

## Tech-Stack

| Bereich | Technologie und Version |
|---|---|
| Backend | PHP `^8.3`, Laravel `^13.17` (lokal installiert: 13.31.0) |
| Datenbank | PostgreSQL 16 (lokale Compose-Konfiguration und CI) |
| Web-Integration | Inertia Laravel 3.3.1 |
| Frontend | Vue 3.5.42, Inertia Vue 3.7.0, TypeScript 5.9.3 |
| Build und Styling | Vite 8.2.2, Tailwind CSS 4.3.3 |
| JavaScript-Laufzeit | Node.js 20.20.2 lokal laut `.nvmrc`; CI verwendet Node.js 22 |
| Routen-Typen | Laravel Wayfinder (PHP 0.1.21, Vite-Plugin 0.1.7) |
| Authentifizierung | Laravel Fortify 1.39.0, Laravel Passkeys (PHP) 0.2.1, `@laravel/passkeys` (JS) 0.2.0 |
| Rollen und Berechtigungen | Spatie Laravel Permission 8.0.0 |
| Qualität und Tests | Pest 5.1.3, Pint 1.30.5, Larastan 3.10.0, ESLint 9.39.5, Prettier 3.9.6 |

Versionsnummern der installierten Pakete stammen aus `composer show --direct` und `npm list --depth=0`; verbindlich für Installationen sind [composer.lock](./composer.lock) und [package-lock.json](./package-lock.json).

## Funktionen

- Registrierung und Anmeldung mit E-Mail und Passwort, E-Mail-Verifizierung, Passwort-Reset, Zwei-Faktor-Authentifizierung und Passkeys.
- Vereine als Mandanten mit Subdomains, Teams, Mitgliedschaften, Einladungen und teambezogenen Berechtigungen.
- Spielplan- und Hallenplanverwaltung inklusive CSV-Vorschau und Import.
- Suche nach Benutzern, Teams, Hallenorten und Einstellungen.
- Datenbankbenachrichtigungen mit Benutzereinstellungen.
- Stripe-Checkout für Vereinsregistrierungen; die Implementierung akzeptiert ausschließlich Stripe-Testschlüssel.

Es gibt keine separate JSON-REST-API unter `routes/api.php`. Der Großteil der Requests sind sessiongebundene Web- und Inertia-Routen; einzelne Aktionen liefern JSON. Details stehen in [docs/api.md](./docs/api.md).

## Schnellstart

### Voraussetzungen

- PHP 8.3 oder neuer (unter 9.0), Composer und Node.js.
- Docker Compose für die lokale PostgreSQL-16-Datenbank.
- Laravel Herd für die in der Konfiguration verwendete lokale Site `https://lazytown.test`.
- Unter Windows verwendet das Vite-Startskript Node.js 20.20.2 aus der Herd-nvm-Installation; siehe [docs/setup.md](./docs/setup.md).

### Installation

```powershell
composer install
Copy-Item .env.example .env
docker compose up -d postgres
php artisan key:generate
php artisan migrate --seed
npm ci
npm run dev
```

Prüfe bei Bedarf die Zugangsdaten in `.env`; `.env.example` und Compose sind auf PostgreSQL mit lokalen Beispielwerten eingerichtet. Der Laravel-Request wird lokal von Herd bedient; `npm run dev` startet Vite mit HMR. Eine ausführliche Anleitung, optionale Demodaten und Fehlerbehebung stehen in [docs/setup.md](./docs/setup.md).

## Dokumentation

Das zentrale Inhaltsverzeichnis befindet sich in [docs/README.md](./docs/README.md). Es führt zu Architektur, Backend, Frontend, Datenbank, Authentifizierung, API-Routen, Modulen/Fachbereichen, Tests, Setup, Deployment und Beitragsregeln.

## CI und Deployment

GitHub Actions führt CI bei Pull Requests, Pushes auf `master` und manuellen Läufen aus. Ein Produktionsdeploy ist nur für `master` nach erfolgreicher CI und bei `DEPLOY_ENABLED=true` vorgesehen. Die Pipeline und ihre Grenzen sind in [CICD_INFO.MD](./CICD_INFO.MD) dokumentiert; die technische Zusammenfassung steht in [docs/deployment.md](./docs/deployment.md).
