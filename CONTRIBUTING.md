# Mitarbeit an LazyTown

Diese Anleitung beschreibt den im Repository erkennbaren Arbeitsablauf. Die CI-Prüfungen in [.github/workflows/tests.yml](./.github/workflows/tests.yml) und die Skripte in [composer.json](./composer.json) / [package.json](./package.json) sind maßgebliche Qualitätsprüfungen.

## Branches und Pull Requests

- `master` ist laut [CICD_INFO.MD](./CICD_INFO.MD) der Produktions-Branch; Pushes dorthin können bei `DEPLOY_ENABLED=true` deployen.
- `dev` existiert als Integrationsbranch; Featurebranches im Repository folgen überwiegend Namen wie `feat/...` oder `fix/...`.
- Für Änderungen einen thematisch begrenzten Featurebranch erstellen und einen Pull Request eröffnen. Der Workflow führt CI auf Pull Requests aus; Pull Requests deployen nicht.
- Vor dem Merge müssen CI-Prüfungen erfolgreich sein. Der Deploy läuft erst nach Merge/Push auf `master` und erfülltem Deploy-Gate.
- Commit-Nachrichtenformat und verbindlicher PR-Zielbranch sind nicht durch Projektkonfiguration festgelegt; siehe TODO in [docs/README.md](./docs/README.md).

## Code-Style und Checks

| Bereich | Befehl |
|---|---|
| PHP Formatprüfung | `composer lint:check` |
| PHP Formatierung | `composer lint` |
| PHP Analyse | `composer types:check` |
| Laravel/Pest-Tests | `php artisan test` |
| JavaScript/TypeScript-Lint | `npm run lint:check` |
| Frontend-Formatprüfung | `npm run format:check` |
| Frontend-Typprüfung | `npm run types:check` |
| Vollständiger CI-Check lokal | `composer ci:check` |

`composer test` kombiniert Config-Cache-Leeren, Pint-Prüfung, PHPStan und Laravel-Tests. `composer ci:check` ergänzt Frontend-Lint, Format und Typprüfung.

## Einen neuen Fachbereich hinzufügen

Das Projekt verwendet keine eigene Laravel-Modulregistrierung. Ein neuer Bereich wird in den vorhandenen Verzeichnissen nach den Mustern der Nachbarbereiche ergänzt:

1. **Verantwortung und Datenmodell klären.** Bestehende Modelle und Beziehungen unter `app/Models/` prüfen. Für Schemaänderungen eine Laravel-Migration erstellen (`php artisan make:migration ...`) und die zugehörige Model-Factory/Testdaten mitdenken.
2. **Request und Zugriff definieren.** Validierung und `authorize()` in einem passenden `app/Http/Requests/<Bereich>/`-Form Request bündeln. Team-/Tenant-Zugriff zusätzlich serverseitig über Middleware, Policy oder Form Request prüfen.
3. **HTTP-Einstieg ergänzen.** Controller unter `app/Http/Controllers/<Bereich>/` anlegen und Route in [routes/web.php](./routes/web.php) oder [routes/settings.php](./routes/settings.php) registrieren. Benannte Routen nutzen; es existiert keine `routes/api.php`.
4. **Fachlogik passend platzieren.** Mehrschrittige Anwendungsfälle als Action unter `app/Actions/<Bereich>/`, wiederverwendbare Logik als Service unter `app/Services/<Bereich>/` modellieren. Für einfache CRUD-Abläufe folgt das Projekt bestehenden Controller-/Eloquent-Mustern; kein Repository-Layer hinzufügen, sofern er nicht ausdrücklich vereinbart ist.
5. **Vue-Seite und Typen ergänzen.** Seiten gehören nach `resources/js/pages/`, wiederverwendbare UI nach `resources/js/components/`, Seitenlogik nach `resources/js/composables/`, Typen nach `resources/js/types/`. Route-/Controller-Helfer nach der Änderung mit `php artisan wayfinder:generate --with-form --no-interaction` neu generieren.
6. **Tests ergänzen.** HTTP- und Berechtigungsverhalten als Pest-Featuretest unter `tests/Feature/` absichern; isolierte Logik kann nach `tests/Unit/`. Vorhandene Factories verwenden.
7. **Dokumentation aktualisieren.** Routen in `docs/api.md`, Datenbankänderungen in `docs/database.md`, passende Fachbereichsseite und Verzeichnisübersicht anpassen.
8. **Checks ausführen.** Mindestens betroffene Tests und `composer ci:check` ausführen, bevor der Pull Request erstellt wird.

## Hinweise

- `.env.example` und Compose-Zugangsdaten sind lokal; echte Secrets nicht committen.
- Team-/Tenant-Berechtigungen niemals nur über Vue-Buttons absichern.
- Demo-Konten dürfen ausschließlich mit `DemoAccountsSeeder` in `local` oder `testing` erzeugt werden.
- Bei Änderungen am Datenbankschema müssen Release-Rollback und Kompatibilität mit der vorherigen Anwendungsversion bedacht werden; siehe [docs/deployment.md](./docs/deployment.md).

