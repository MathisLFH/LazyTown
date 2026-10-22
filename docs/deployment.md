# Build und Deployment

Das Repository enthält einen GitHub-Actions-Workflow für CI und einen optional aktivierbaren SSH-Release-Deploy auf `master`. Der Workflow deployt keine Anwendung per Docker; die Compose-Datei stellt nur PostgreSQL für die lokale Entwicklung bereit.

## CI

Workflow: [.github/workflows/tests.yml](../.github/workflows/tests.yml).

- Auslöser: Pull Request, Push auf `master` und `workflow_dispatch`.
- Laufzeit: Ubuntu, PHP 8.5, Node.js 22, PostgreSQL 16 als temporärer Service.
- Checks: `composer setup`, anschließend `composer ci:check` (ESLint, Prettier, TypeScript, Pint, PHPStan und Pest).
- Die CI-Datenbank wird mit dem Runner verworfen; sie ist nicht die Produktionsdatenbank.

## Produktionsdeploy

Ein Deploy-Job wird nur nach erfolgreicher CI gestartet, wenn der Lauf auf `master` liegt, kein Pull Request ist und die Repository-Variable `DEPLOY_ENABLED` exakt `true` lautet. Die GitHub-Environment `production` liefert `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH`, `DEPLOY_URL`, `DEPLOY_SSH_KEY` und `DEPLOY_KNOWN_HOSTS`.

Der Workflow baut ein Releasearchiv, generiert Wayfinder-Routen und Vite-Assets, installiert Composer-Produktionsabhängigkeiten und überträgt das Archiv per SSH. Auf dem Server liegen `.env` und `storage` gemeinsam unter `/var/www/lazytown/shared`; jede Release liegt unter `releases/`, der Webroot zeigt auf `current/public`. Für den PHP-FastCGI-Handler muss `SCRIPT_FILENAME` den aufgelösten Releasepfad verwenden (`$realpath_root$fastcgi_script_name`), nicht den stabilen Symlinkpfad aus `$document_root`; sonst kann PHP bei atomaren Symlink-Wechseln noch Code aus einem anderen Release ausführen als Nginx für die statischen Assets ausliefert. Vor der Aktivierung prüft der Workflow das Vite-Manifest und alle darin referenzierten Dateien. Nach der Umschaltung von `current` prüft er die von `DEPLOY_URL` ausgelieferten CSS-/JavaScript-Dateien einschließlich Content-Type; bei Fehlern wird ein Rollback versucht.

Die vollständigen Vorbedingungen, Rechte und Wiederherstellungsschritte stehen in [CICD_INFO.MD](../CICD_INFO.MD).

## Produktionskonfiguration und Betrieb

| Einstellung | Zweck / Vorgabe |
|---|---|
| `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY` | Laravel-Betriebsmodus, Debug-Verhalten, URL und Verschlüsselung |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Dauerhafte PostgreSQL-Verbindung; nur in der Server-`.env` |
| `SESSION_*`, `CACHE_STORE`, `QUEUE_CONNECTION` | Session, Cache und Queue; Beispiel setzt DB-Treiber ein |
| `MAIL_*` | In `.env.example` auf Log-Mailer voreingestellt; produktiven Mailer separat konfigurieren |
| `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_ANNUAL_ACCESS_AMOUNT_CENTS` | Stripe-Testintegration; Checkout-Service akzeptiert ausschließlich `sk_test_`/`whsec_`-Werte |
| `VITE_APP_NAME`, `INERTIA_SSR_ENABLED`, `INERTIA_SSR_URL` | Frontend-Titel beziehungsweise Inertia-SSR-Konfiguration |

Produktionssecrets gehören ausschließlich in die geschützte Server-`.env` oder den GitHub-Secret-Speicher, nicht in Git. `.env.example` und `docker-compose.yml` enthalten einfache lokale Datenbankbeispielwerte und sind keine Produktionsvorlage.

Der Workflow startet keinen dauerhaften Queue-Worker oder Scheduler. Für `ShouldQueue`-Notifications und die tägliche Einladungslöschung muss der entsprechende Prozess außerhalb der Releasepipeline eingerichtet werden. Der Code-Rollback setzt angewendete Migrationen nicht zurück; vor riskanten Schemaänderungen sind Datenbank-Backups erforderlich.
