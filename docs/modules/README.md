# Fachbereiche der Anwendung

LazyTown ist fachlich gruppiert, aber technisch kein Modulpaket-System. Diese Dokumente verwenden „Fachbereich“ für zusammengehörige Laravel- und Vue-Dateien, die innerhalb derselben Anwendung laufen und dieselben Modelle, Middleware und Datenbank teilen.

## Übersicht

| Fachbereich | Verantwortung | Dokument |
|---|---|---|
| Authentifizierung | Fortify, Registrierung, Passwortflüsse, 2FA, Passkeys und Verifizierung | [authentifizierung.md](./authentifizierung.md) |
| Teams | Mandanten, Vereine, Mannschaften, Mitgliedschaften, Rollen, Einladungen, Verwaltung und Zahlungen | [teams.md](./teams.md) |
| Pläne | Spielplan, Hallenplan, CSV-Import und Änderungsbenachrichtigungen | [plaene.md](./plaene.md) |
| Einstellungen | Profil, Sicherheitsseite, Benachrichtigungspräferenzen und Datenexport | [einstellungen.md](./einstellungen.md) |
| Benachrichtigungen | Datenbankbenachrichtigungen, Status und Testbenachrichtigung | [benachrichtigungen.md](./benachrichtigungen.md) |
| Suche | Kontextbezogene Vorschläge für Benutzer, Teams, Hallen und Einstellungen | [suche.md](./suche.md) |
| Startseite und öffentliche Seiten | Willkommensseite, angemeldeter Überblick und statische Informationsseiten | [startseite.md](./startseite.md) |

## Wie die Bereiche zusammenarbeiten

- Die Fachbereiche werden durch Laravel-Routen in [routes/web.php](../../routes/web.php) und [routes/settings.php](../../routes/settings.php) angesprochen.
- Authentifizierte Requests teilen über `HandleInertiaRequests` Benutzer-, Team-, Tenant- und Notification-Props mit Vue.
- Vereins- und Teamzugriff wird über `Tenant`, `Team`, `Membership`, Middleware und Policies geprüft; er ist nicht allein eine UI-Eigenschaft.
- Planspeicherungen verwenden `ScheduleUpdateNotifier`; Einladungen verwenden die Benachrichtigungslogik der Teamverwaltung.
- Es gibt keine dedizierten Modul-Provider, modulare Migrationen oder Modulschnittstellen. Neue Fachlogik folgt der bestehenden Struktur; die Schritte stehen in [CONTRIBUTING.md](../../CONTRIBUTING.md).

