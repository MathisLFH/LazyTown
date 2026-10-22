# LazyTown CI/CD: Ablauf und Betrieb

Stand: 21. Oktober 2026

Dieses Dokument beschreibt, wie Änderungen von GitHub bis zur Produktionsseite gelangen, was dabei mit der PostgreSQL-Datenbank passiert und welche Betriebsaufgaben noch offen sind. Der Workflow liegt unter [`.github/workflows/tests.yml`](./.github/workflows/tests.yml).

## Kurzfassung

- Pull Requests führen CI aus. Ein Pull Request deployt **nicht**.
- Pushes auf `master` führen CI aus. Wenn CI erfolgreich ist und die Repository-Variable `DEPLOY_ENABLED` den Wert `true` hat, folgt automatisch ein Production-Deploy.
- Ein manueller Lauf über `workflow_dispatch` kann ebenfalls deployen, aber nur auf `master` und nur mit `DEPLOY_ENABLED=true`.
- CI verwendet eine temporäre PostgreSQL-16-Datenbank. Sie ist nicht die Produktionsdatenbank.
- Beim Deploy werden ausstehende Laravel-Migrationen auf der bestehenden Produktionsdatenbank ausgeführt. Datenbankänderungen werden durch einen Code-Rollback **nicht** zurückgenommen.

## Was bei einem Lauf passiert

### 1. CI

Der Job `ci` läuft auf GitHub Actions mit PHP 8.5, Node.js 22 und einem temporären PostgreSQL-16-Service. Die Testdatenbank heißt `lazytown`; ihre Test-Zugangsdaten werden nur dem Runner bereitgestellt.

Der Job führt `composer setup` aus. Das richtet die Anwendung im Runner ein, erzeugt dort eine temporäre `.env`, führt Migrationen gegen den temporären PostgreSQL-Service aus und baut die Frontend-Assets. Danach führt `composer ci:check` unter anderem Formatierungs- und Lint-Prüfungen, TypeScript-/PHP-Analyse und die Tests aus.

Die CI-Datenbank wird mit dem Runner verworfen. CI verwendet weder die Produktions-`.env` noch die Produktionsdatenbank.

### 2. Deploy-Gate

Der Job `Deploy to production` startet nur, wenn alle Bedingungen erfüllt sind:

1. `ci` war erfolgreich.
2. Der Lauf bezieht sich auf `master`.
3. Das Ereignis ist kein Pull Request.
4. Die **Repository-Variable** `DEPLOY_ENABLED` ist exakt `true`.
5. Eine gegebenenfalls konfigurierte Freigabe für die GitHub-Environment `production` wurde erteilt.

Ist `DEPLOY_ENABLED=false` oder fehlt die Variable, läuft CI trotzdem; der Deploy-Job wird übersprungen. Das Ändern der Variable startet keinen Lauf von selbst.

GitHub-Konfiguration:

- **Repository variable** `DEPLOY_ENABLED`: steuert, ob `master` deployen darf.
- In der GitHub-Environment `production` liegen die Variablen `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH` und `DEPLOY_URL`.
- Ebenfalls in `production` liegen die Secrets `DEPLOY_SSH_KEY` und `DEPLOY_KNOWN_HOSTS`.
- `DEPLOY_SSH_KEY` ist der private Deploy-Schlüssel. `DEPLOY_KNOWN_HOSTS` enthält öffentliche SSH-Hostschlüssel zur Serverprüfung. Diese beiden Werte sind unterschiedliche Dinge.

### 3. Release auf dem Produktionsserver

Der Deploy-Job baut auf GitHub eine Release-Datei. Die Produktions-`.env`, Laufzeitdaten und `storage` werden nicht in dieses Paket aufgenommen. Der Job verbindet sich mit festgelegtem Host-Key-Checking und prüft vor dem Upload unter anderem:

- den erwarteten Pfad `/var/www/lazytown`,
- `releases`, `shared` und `shared/.env`,
- Lesbarkeit und Rechte der `.env` (`640`, Gruppe `www-data`),
- PHP-Version und benötigte Serverwerkzeuge,
- dass der Deploy-Benutzer die nötigen Rechte hat.

Das Paket wird in einem neuen Verzeichnis unter `releases/` entpackt. Die neue Release verwendet Symlinks auf `/var/www/lazytown/shared/.env` und `/var/www/lazytown/shared/storage`. Danach führt der Workflow `package:discover`, Migrationen und Laravel-Optimierungen aus. Erst anschließend wird `current` atomar auf die neue Release umgeschaltet.

Die Nginx-Konfiguration muss bei atomaren Release-Symlinks für PHP-FPM den aufgelösten Pfad übergeben: `fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;`. Die Standardzeile `$document_root$fastcgi_script_name` kann dazu führen, dass PHP-Code aus einem anderen Release ausgeführt wird als die statischen Dateien unter `current/public`. Nach Änderungen an der Nginx-Konfiguration `nginx -t` ausführen und Nginx neu laden.

Vor der Aktivierung prüft der Workflow, ob das Vite-Manifest und alle darin referenzierten Assets im entpackten Release vorhanden sind. Nach der Umschaltung ruft er `DEPLOY_URL` per HTTPS ab und prüft zusätzlich, ob die referenzierten CSS-/JavaScript-Dateien erreichbar sind und passende Content-Types liefern. Bei Fehlern versucht der Workflow einen Rollback auf die vorherige Release. Dieser Health-Check bestätigt nicht jeden Anwendungsablauf oder jede externe Integration.

## Was mit der Produktionsdatenbank passiert

Die Anwendung verwendet die **dauerhafte** PostgreSQL-Datenbank, die in `/var/www/lazytown/shared/.env` konfiguriert ist. Für die derzeitige Installation heißt sie `lazytown_production`; Zugangsdaten bleiben ausschließlich in der Server-`.env`.

Bei jedem Deploy wird auf dem Server Folgendes ausgeführt:

```bash
php artisan migrate --force
```

Laravel vergleicht die vorhandenen Einträge in der Tabelle `migrations` mit den Migrationen im Release und führt nur noch nicht angewendete Migrationen aus. Der Deploy führt **keinen** Seeder aus und erstellt nicht jedes Mal eine frische Produktionsdatenbank.

Die Daten bleiben grundsätzlich erhalten. Eine Migration kann sie jedoch verändern oder löschen, wenn ihr Code das tut. Daher vor jedem riskanten Schema- oder Datenumbau:

1. Migration prüfen und lokal/auf einer Testdatenbank ausführen.
2. Ein aktuelles PostgreSQL-Backup außerhalb des VPS erstellen und dessen Wiederherstellung regelmäßig testen.
3. Migrationen so planen, dass die vorherige Anwendungsversion mit dem neuen Schema noch funktioniert („expand and contract“: erst kompatibel ergänzen, später alte Felder entfernen).

**Ein Code-Rollback ist kein Datenbank-Rollback.** Schlägt die Migration fehl, kann sie teilweise schon Änderungen durchgeführt haben. Schlägt der Health-Check nach dem Aktivieren der neuen Release fehl, schaltet der Workflow `current` auf die vorherige Release zurück, sofern diese existiert. Bereits angewendete Migrationen und veränderte Daten bleiben bestehen. Die erste erfolgreiche Release hatte noch keinen Rollback-Vorgänger; bei späteren Deploys wird die bisherige Release als `previous` festgehalten.

## Fehler und Wiederherstellung

- **CI schlägt fehl:** Nicht deployen. Den ersten roten Schritt in den Actions-Logs beheben.
- **SSH-Konfiguration oder Preflight schlägt fehl:** Der Job bricht vor dem Release-Wechsel ab. Fehlermeldung und Variablen-/Secret-Namen prüfen; keine Secret-Werte in Logs oder Chat kopieren.
- **Migration oder Installationsschritt schlägt fehl:** Logs prüfen. `current` wird erst nach Migration und Optimierung umgeschaltet; die Datenbank kann trotzdem bereits einzelne erfolgreiche Migrationen enthalten.
- **Health-Check schlägt fehl:** Der Workflow versucht, `current` auf `previous` zurückzustellen. Das funktioniert nur, wenn ein gültiger Vorgänger existiert. Die Datenbank bleibt auf dem erreichten Migrationsstand.
- **Manuelle Wiederherstellung:** Datenbank nur aus einem bekannten, geprüften Backup wiederherstellen. Das kann Daten nach dem Backup-Zeitpunkt verlieren und ist kein automatischer Teil des Workflows.

Der Workflow entfernt das hochgeladene Archiv nach dem Entpacken, löscht aber **keine alten Release-Verzeichnisse**. Speicherplatz auf dem Server überwachen; eine Retention-Regel sollte mindestens `current` und `previous` behalten.

## Was du nach einem grünen Deploy tun solltest

1. Die Live-Seite öffnen und einen normalen Nutzerablauf manuell testen, insbesondere Funktionen, die Daten aus PostgreSQL lesen oder schreiben.
2. Zahlungs-, Mail- und andere externe Integrationen separat prüfen. Ein grüner Deploy bestätigt nicht, dass zum Beispiel Stripe-Live-Schlüssel und Webhooks korrekt eingerichtet sind.
3. Logs und Server-Speicherplatz nach dem ersten Betrieb kontrollieren.
4. Regelmäßige, externe Datenbank-Backups und einen Wiederherstellungstest einplanen.
5. `DEPLOY_ENABLED=true` nur beibehalten, wenn **jeder Push oder Merge nach `master`** automatisch live gehen soll. Auf `false` gesetzt läuft CI weiterhin, aber künftige Deploy-Jobs werden übersprungen.

## Branches: aktueller Stand und Empfehlung

- PR [#5](https://github.com/MathisLFH/LazyTown/pull/5) von `feat/production-deploy` nach `master` wurde am 15. Oktober 2026 gemergt.
- Der erfolgreiche Production-Lauf war Run [#20](https://github.com/MathisLFH/LazyTown/actions/runs/38712716178) auf `master`; die Produktionsseite antwortete danach.
- `master` ist der Produktions-Branch. Der Merge von `feat/production-deploy` nach `dev` am 21. Oktober 2026 ändert `master` nicht.
- Der Merge liegt zunächst nur im lokalen Branch `dev`; `origin/dev` wird dadurch nicht aktualisiert.
- Der Workflow reagiert auf Pull Requests, Pushes auf `master` und manuelle `workflow_dispatch`-Läufe. Ein direkter Push auf `dev` löst weder CI noch Deploy aus; ein Pull Request nach `dev` führt CI aus, deployt aber nicht.
- Ein Push auf `master` löst CI aus. Nach erfolgreicher CI wird der Deploy-Job nur ausgeführt, wenn `DEPLOY_ENABLED` exakt `true` ist und die nötige Freigabe der `production`-Environment vorliegt. Die Variable allein startet keinen Lauf.

Da ein Push auf `master` bei aktivem Deploy-Gate unmittelbar Produktion auslösen kann, sollte `master` geschützt sein: Änderungen nur per Pull Request zulassen, den Check `ci` verlangen und Force-Pushes sowie das Löschen des Branches sperren. Eine Freigabe für die `production`-Environment kann zusätzlich den Deploy-Job absichern.
