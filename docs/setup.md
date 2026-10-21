# Entwicklungsumgebung einrichten

Diese Anleitung richtet eine lokale LazyTown-Entwicklungsumgebung ein. Die Beispielkonfiguration verwendet PostgreSQL und die Herd-Site `https://lazytown.test`; sie ist nicht für Produktion gedacht.

## Voraussetzungen

| Werkzeug | Erforderlicher Stand / Hinweis |
|---|---|
| PHP | `^8.3` laut [composer.json](../composer.json); der CI- und Produktionsworkflow verwendet PHP 8.5 |
| Composer | Composer 2 wird in CI eingerichtet |
| Node.js | `.nvmrc` nennt 20.20.2. Das Vite-Skript fordert diese Version unter Windows über Herd-nvm an |
| npm | Lockfile liegt als [package-lock.json](../package-lock.json) vor |
| Docker Compose | Startet nur PostgreSQL 16, nicht PHP oder Node |
| Laravel Herd | Empfohlener lokaler Webserver für `https://lazytown.test` |

## Schritt für Schritt

### 1. Abhängigkeiten installieren

```powershell
composer install
npm ci
```

`npm ci` verwendet das versionierte Lockfile und installiert keine abweichenden Paketversionen.

### 2. Lokale Datenbank starten

```powershell
docker compose up -d postgres
```

[docker-compose.yml](../docker-compose.yml) stellt PostgreSQL 16 auf Port `5432` bereit. Die Werte `lazytown`, `admin` und `lazytown` sind lokale Beispieldaten. Passe `.env` und Compose an, wenn Port oder Zugangsdaten lokal abweichen.

### 3. Umgebungsdatei anlegen

```powershell
Copy-Item .env.example .env
```

Die Vorlage konfiguriert PostgreSQL, `APP_URL=https://lazytown.test`, `SESSION_DOMAIN=.lazytown.test`, Datenbank-Session/Cache/Queue und `MAIL_MAILER=log`. Stelle sicher, dass Herd die Site `lazytown.test` auf dieses Repository zeigt.

### 4. Schlüssel erzeugen und Datenbank einrichten

```powershell
php artisan key:generate
php artisan migrate --seed
```

`DatabaseSeeder` erstellt den Basis-Testbenutzer und führt `TeamPermissionSeeder` aus. Für zusätzliche Demo-Vereine und Demo-Benutzer kann im lokalen Umfeld separat ausgeführt werden:

```powershell
php artisan db:seed --class=DemoAccountsSeeder
```

Der Demo-Seeder ist im Code auf die Umgebungen `local` und `testing` beschränkt. Seine Zugangsdaten stehen im Seeder und sind ausschließlich für lokale Tests bestimmt.

### 5. Frontend starten

```powershell
npm run dev
```

Das Skript [scripts/run-vite.js](../scripts/run-vite.js) startet Vite. Unter Windows erwartet es Node.js `20.20.2` unter dem in diesem Skript festgelegten Herd-nvm-Pfad. Vite verwendet HMR über `wss://lazytown.test:5173`. Die Anwendung selbst wird lokal über Herd bereitgestellt.

Alternativ startet `npm run start` [start.js](../start.js); dieses Skript startet Vite, führt `docker compose up -d` aus und öffnet nach kurzer Wartezeit Herd im Browser. Es führt keine Migrationen aus.

## Verfügbare Projektbefehle

| Befehl | Wirkung laut Projektdateien |
|---|---|
| `composer setup` | Installiert Composer-Pakete, kopiert bei Bedarf `.env`, generiert den Schlüssel, migriert mit `--force`, führt `npm ci` und `npm run build` aus |
| `composer test` | Leert Config-Cache, prüft Pint und PHPStan und startet `php artisan test` |
| `composer ci:check` | Startet JS-Lint, Prettier-Prüfung, TypeScript-Prüfung und anschließend `composer test` |
| `npm run dev` | Startet Vite über den Node-Versionswrapper |
| `npm run build` | Erstellt Produktionsassets mit Vite |
| `npm run lint` / `npm run lint:check` | ESLint mit beziehungsweise ohne automatische Fixes |
| `npm run format` / `npm run format:check` | Prettier für `resources/` mit beziehungsweise ohne Schreibzugriff |
| `npm run types:check` | `vue-tsc --noEmit` |

Die vollständigen Definitionen stehen in [composer.json](../composer.json) und [package.json](../package.json).

## Häufige Probleme

| Symptom | Prüfen / Lösung |
|---|---|
| `could not connect` zu PostgreSQL | Läuft `docker compose up -d postgres`? Stimmen `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` und `DB_PASSWORD` mit Compose überein? |
| Herd-Seite nicht erreichbar | Herd-Site `lazytown.test` auf das Projekt zeigen lassen; `APP_URL` und `SESSION_DOMAIN` nicht versehentlich auf eine andere Site setzen |
| `Node.js 20.20.2 was not found` unter Windows | `.nvmrc` und den Herd-nvm-Pfad in `scripts/run-vite.js` prüfen; nach einem Node-Wechsel ein neues Terminal öffnen |
| `Vite was not found` | `npm ci` im Projekt-Root ausführen |
| Asset-Manifest fehlt | Für den Entwicklungsserver `npm run dev`, für statische Assets `npm run build` ausführen |
| Anmeldung funktioniert auf abweichendem Host nicht | `APP_URL`, `SESSION_DOMAIN` und den Vite-HMR-Host in [vite.config.ts](../vite.config.ts) gemeinsam prüfen |
| `composer setup` scheitert bei Migrationen | Das Skript startet PostgreSQL nicht; zuerst den Compose-Service starten und DB-Werte kontrollieren |

