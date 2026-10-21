# Glossar

| Begriff | Bedeutung im Projekt |
|---|---|
| **Tenant** | Verein als Mandant. Datensatz in `tenants`; wird über eine Subdomain identifiziert. |
| **Team** | Mannschaft, die einem Tenant/Verein gehört. Datensatz in `teams`, nicht gleichbedeutend mit dem Verein. |
| **Mitgliedschaft / Membership** | Beziehung zwischen User und Team in `team_members`; enthält Teamrolle, Status und optionale Tenant-/Permission-Rollen-IDs. |
| **Globale User-Rolle** | Rolle wie `spieler`, `trainer` oder `verwaltung`, im User-JSON-Feld `roles` gespeichert. |
| **Teamrolle** | Mitgliedschaftsrolle `owner`, `admin` oder `member`; gilt für eine einzelne Teammitgliedschaft. |
| **Berechtigung / Permission** | Fine-grained Action-Recht, z. B. `team:update` oder `member:add`; Spatie Laravel Permission verwaltet diese im Team-Scope. |
| **Aktuelles Team** | Team, das `users.current_team_id` referenziert und als UI-/URL-Kontext dient. |
| **Inertia** | Verbindet Laravel-Routen und Controller mit Vue-Seiten und Props, ohne dass ein separater Vue Router benötigt wird. |
| **Wayfinder** | Generiert typisierte TypeScript-Helfer für Laravel-Controller und benannte Routen. |
| **Spielplan** | Saisonbezogene Spiele einer Mannschaft in der Tabelle `matches` (Eloquent-Modell `ClubMatch`). |
| **Hallenplan** | Trainingszeiten mit Wochentag, Uhrzeit, Team und optionaler Halle in `trainings`. |
| **Gym / Hallenort** | Halle als Datensatz in `gyms`; `tenant_id = NULL` bedeutet gemeinsame Halle, sonst Tenant-eigene Halle. |
| **Notification** | Laravel-Benachrichtigung, die für den Benutzer in `notifications` gespeichert wird; ausgewählte Notifications werden auch per Mail gesendet. |
| **Fortify** | Laravel-Paket, das Authentifizierungsrouten und Backend-Logik bereitstellt; dieses Projekt rendert die UI mit Inertia/Vue. |
| **Passkey** | WebAuthn-Anmeldedaten, die vom Laravel-Passkeys-Stack verwaltet und in `passkeys` gespeichert werden. |
| **Fachbereich** | In dieser Dokumentation eine fachlich gruppierte Sammlung von Dateien; kein separat registriertes Laravel-Modul. |
| **Herd** | Lokaler Laravel-Webserver, den das Projekt für die Site `lazytown.test` voraussetzt. |

