# Projektstruktur

Der Baum zeigt den Anwendungscode und die wichtigsten Konfigurations- und Betriebsdateien. `vendor/` und `node_modules/` sind installierte Abhängigkeiten; generierte Vite-Builds und Wayfinder-Dateien sind keine handgepflegten Quellmodule.

```text
LazyTown/
├── app/                         # Laravel-Anwendung, nach technischen und fachlichen Zuständigkeiten
│   ├── Actions/                 # Abgeschlossene Anwendungsfälle, z. B. Teams und Fortify
│   ├── Concerns/                # Wiederverwendbare Traits und Validierungsregeln
│   ├── Data/                    # Geformte Datenobjekte für Vue-Props/Berechtigungen
│   ├── Enums/                   # Rollen und Berechtigungen
│   ├── Http/
│   │   ├── Controllers/         # HTTP-Einstiegspunkte, nach Teams/Plans/Settings gruppiert
│   │   ├── Middleware/          # Auth-, Tenant-, Rollen- und Inertia-Kontext
│   │   ├── Requests/            # Validierung und Request-Autorisierung
│   │   └── Responses/           # Fortify-Antworten und Redirect-Helfer
│   ├── Models/                  # Eloquent-Modelle
│   ├── Notifications/           # Benachrichtigungstypen
│   ├── Policies/                # Modellbezogene Autorisierungsregeln
│   ├── Providers/                # App- und Fortify-Konfiguration
│   ├── Rules/                    # Wiederverwendbare Validierungsregeln
│   ├── Search/                   # Suchkontext, Provider und Aggregation
│   └── Services/                 # Wiederverwendbare Plan-, Team- und Notification-Logik
├── bootstrap/                   # Laravel-Bootstrap, Middleware und Providerregistrierung
├── config/                      # Laravel- und Paketkonfiguration
├── database/
│   ├── factories/               # Test- und Seed-Daten
│   ├── migrations/              # Schemaänderungen
│   └── seeders/                 # Basis-, Berechtigungs- und Demo-Seeder
├── docs/                        # Projektdokumentation
│   └── modules/                 # Beschreibungen der fachlichen Codebereiche
├── public/                      # Webroot und öffentliche Assets
├── resources/
│   ├── css/                     # Tailwind-Einstiegspunkt und Design-Tokens
│   ├── images/                  # Quellbilder
│   ├── js/
│   │   ├── components/          # Wiederverwendbare und fachlich gruppierte Vue-Komponenten
│   │   ├── composables/         # Vue Composition API-Helfer
│   │   ├── layouts/             # App-, Auth-, Kontakt- und Einstellungs-Layouts
│   │   ├── lib/                 # Frontend-Hilfsfunktionen
│   │   ├── pages/               # Von Laravel/Inertia gerenderte Seiten
│   │   └── types/               # Frontend-Typen
│   └── views/                   # Laravel-Root-View für Inertia
├── routes/                      # Web- und Konsolenrouten
├── scripts/                     # Hilfsskripte für lokale Entwicklung
│   └── run-vite.js              # Verwendet die lokale Node-Version zum Start von Vite
├── storage/                     # Laufzeitdaten, Logs, Cache, Sessions und öffentliche Uploads
├── tests/
│   ├── Feature/                 # HTTP- und Integrationsverhalten
│   └── Unit/                    # Isolierte Tests
├── .github/workflows/           # GitHub Actions CI und Deployment
├── .env.example                 # Lokale Beispielkonfiguration
├── .nvmrc                       # Node-Version für lokale Entwicklung
├── docker-compose.yml           # Lokaler PostgreSQL-Service
├── composer.json                # PHP-Abhängigkeiten und Composer-Skripte
├── composer.lock                # Aufgelöste PHP-Abhängigkeiten
├── package.json                 # Frontend-Abhängigkeiten und npm-Skripte
├── package-lock.json            # Aufgelöste JavaScript-Abhängigkeiten
├── vite.config.ts               # Vite-Plugins, Eingänge und HMR
├── start.js                     # Startet lokale Entwicklung und Compose-Service
└── README.md                    # Einstieg und Schnellstart
```

## Wichtige Einstiegspunkte

| Datei / Ordner | Zweck |
|---|---|
| [composer.json](../composer.json) | PHP-Anforderungen, Laravel-Pakete, Autoloading und Composer-Skripte |
| [package.json](../package.json) | Vue-/Vite-Abhängigkeiten, Lint-, Format-, Typ- und Build-Skripte |
| [bootstrap/app.php](../bootstrap/app.php) | Web-/Console-Routing, Middleware-Aliase, CSRF-Ausnahme und Fehlerformat |
| [bootstrap/providers.php](../bootstrap/providers.php) | Registrierung von `AppServiceProvider` und `FortifyServiceProvider` |
| [routes/web.php](../routes/web.php) | Landing-, Vereins-, Plan-, Benachrichtigungs- und Suchrouten |
| [routes/settings.php](../routes/settings.php) | Profileinstellungen, Sicherheit, Teams und Verwaltung |
| [routes/console.php](../routes/console.php) | Tägliche Bereinigung abgelaufener Team-Einladungen |
| [resources/js/app.ts](../resources/js/app.ts) | Inertia-App, Layoutauswahl, Theme und Flash-Toasts |
| [resources/views/app.blade.php](../resources/views/app.blade.php) | HTML-Root-View und Vite-/Inertia-Einbindung |
| [vite.config.ts](../vite.config.ts) | Vite-Plugins und lokales HMR |
| [phpunit.xml](../phpunit.xml) | Pest/PHPUnit-Suites und Testumgebung |
| [.github/workflows/tests.yml](../.github/workflows/tests.yml) | CI und Produktion-Deployment |
| [scripts/run-vite.js](../scripts/run-vite.js) | Vite-Start mit Node-Versionsprüfung |
| [start.js](../start.js) | Startet Vite, PostgreSQL via Compose und öffnet die Herd-Site |
| [AGENTS.md](../AGENTS.md) | Projektweite Entwicklungs- und Laravel-Konventionen |
| [CICD_INFO.MD](../CICD_INFO.MD) | CI-/Deployment-Ablauf und Betriebshinweise |

## Fachliche Verzeichnisse

Die Domänen sind keine autonom ladbaren Laravel-Module. Ihre Dateien sind nach Verantwortung gruppiert:

- **Teams:** [app/Http/Controllers/Teams](../app/Http/Controllers/Teams), [app/Actions/Teams](../app/Actions/Teams), [app/Services/Teams](../app/Services/Teams) und `app/Models/`.
- **Pläne:** [app/Http/Controllers/Plans](../app/Http/Controllers/Plans), [app/Services/Plans](../app/Services/Plans) und [resources/js/components/Plans](../resources/js/components/Plans).
- **Authentifizierung:** [app/Providers/FortifyServiceProvider.php](../app/Providers/FortifyServiceProvider.php), [app/Actions/Fortify](../app/Actions/Fortify) und `app/Http/Responses/`.
- **Suche:** [app/Search](../app/Search), aufgeteilt in Provider, Kontext und Service.
- **Benutzeroberfläche:** [resources/js/pages](../resources/js/pages), [resources/js/components](../resources/js/components), [resources/js/composables](../resources/js/composables) und [resources/js/layouts](../resources/js/layouts).

Es gibt kein `routes/api.php`, kein `app/Repositories/`, kein `app/Modules/` und im Anwendungscode keine eigenen Verzeichnisse `app/Events/` oder `app/Jobs/`. Generierte Wayfinder-Dateien unter `resources/js/actions/` und `resources/js/routes/` werden durch `.gitignore` ausgeschlossen und in der CI-Deploypipeline generiert.

## Weitere Projektdateien

[Datenplan.md](../Datenplan.md), [Suchplan.md](../Suchplan.md) und [MULTI_TENANCY_IMPLEMENTATION_PLAN.md](../MULTI_TENANCY_IMPLEMENTATION_PLAN.md) sind Planungsnotizen. Sie sind nicht automatisch eine Beschreibung des heutigen Stands; aktuelle Umsetzung und Abweichungen stehen in dieser Dokumentation.
