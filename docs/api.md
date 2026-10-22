# HTTP-Routen und API

LazyTown stellt keine separate REST-API bereit: Es gibt keine `routes/api.php` und keine API-Versionierung. Die fachlichen HTTP-Schnittstellen sind überwiegend sessiongebundene Web-/Inertia-Routen. Einige wenige Endpunkte antworten direkt mit JSON. Die Tabellen unten dokumentieren alle 57 projektspezifischen, nicht vom Vendor stammenden Routen sowie die Auth-Routen von Fortify/Passkeys.

## Antworttypen

| Typ | Bedeutung |
|---|---|
| Inertia | Laravel rendert eine Vue-Seite mit Props; Folgeaufrufe erfolgen mit Inertia-Headern |
| Redirect | Nach erfolgreicher Formularaktion meist Redirect auf eine benannte Route oder zurück |
| JSON | Direkte JSON-Antwort einer Vorschau-/Suchroute |
| Download | JSON-Datei als Attachment |
| Stripe | Externer Webhook mit Signature-Header und 204/400 Antwort |

Form Requests, konkrete Regeln und Controller sind in den [Fachbereichsdokumenten](./modules/README.md) verlinkt. Für Webformulare sind Session-Cookie und CSRF-Token (außer Stripe-Webhook) erforderlich.

## Öffentliche und gemeinsame Routen

| Methode | URI | Auth erforderlich | Eingabe / Beispiel | Antwort |
|---|---|---:|---|---|
| GET | `/` | Nein | Keine | Inertia `Welcome` oder angemeldetes Dashboard |
| GET | `/startseite` | Nein | Keine | Inertia `Welcome` oder angemeldetes Dashboard |
| GET | `/contact/about-us` | Nein | Keine | Inertia `AboutUs` |
| GET | `/contact/kontakt` | Nein | Keine | Inertia `Kontakt` |
| GET | `/contact/impressum` | Nein | Keine | Inertia `Impressum` |
| GET | `/contact/datenschutzerklaerung` | Nein | Keine | Inertia `Datenschutzerklaerung` |
| GET | `/.well-known/passkey-endpoints` | Nein | Keine | JSON mit Enrollment-/Management-URLs |
| POST | `/stripe/webhook` | Nein; Stripe-Signatur erforderlich | Stripe-Event-Raw-Body und `Stripe-Signature`-Header | `204 No Content`, bei fehlender/ungültiger Signatur `400` |

## Authentifizierung (Fortify und Passkeys)

Fortify-/Passkeys-Routen verwenden `web`; die Paketcontroller bestimmen weitere Authentifizierungs- und Passwortbestätigungsanforderungen. Erfolgsantworten folgen dem Session-/Inertia-Verhalten, nicht einem stabilen REST-JSON-Schema.

| Methode | URI | Auth erforderlich | Eingabe | Antwort / Beispiel |
|---|---|---:|---|---|
| GET | `/login` | Nein | Keine | Inertia `auth/Login` |
| POST | `/login` | Nein | `email`, `password`; optionaler Einladungs-Kontext in Query | Session-Login, Redirect oder Validierungsfehler |
| POST | `/logout` | Ja | Keine | Session beenden, Redirect |
| GET | `/register` | Nein | Keine | Inertia `auth/Register` |
| POST | `/register` | Nein | Spieler: Profil/E-Mail/Passwort; Vereinsanmeldung zusätzlich `club_name`, `subdomain`, `subscription` | Benutzer/Tenant/Team anlegen, Redirect |
| GET / POST | `/forgot-password` | Nein | POST: `email` | Reset-Formular beziehungsweise Status/Redirect |
| GET | `/reset-password/{token}` | Nein | Token im URI | Inertia `auth/ResetPassword` |
| POST | `/reset-password` | Nein | `token`, `email`, `password`, `password_confirmation` | Passwort speichern, Redirect |
| GET | `/email/verify` | Ja | Keine | Verifizierungsseite |
| GET | `/email/verify/{id}/{hash}` | Ja | Signierte ID und Hash | E-Mail verifizieren, Redirect |
| POST | `/email/verification-notification` | Ja | Keine | Neue Verifizierungs-Mail / Status |
| GET / POST | `/user/confirm-password` | Ja | POST: `password` | Passwortbestätigung |
| GET | `/user/confirmed-password-status` | Ja | Keine | Bestätigungsstatus |
| GET / POST | `/two-factor-challenge` | 2FA-Session | Code oder Recovery-Code | 2FA abschließen, Redirect |
| POST / DELETE | `/user/two-factor-authentication` | Ja | 2FA-Aktivierung/-Deaktivierung | Status-/Redirectantwort |
| GET | `/user/two-factor-secret-key` | Ja | Keine | 2FA-Geheimnis |
| GET | `/user/two-factor-qr-code` | Ja | Keine | QR-Code-Darstellung |
| GET / POST | `/user/two-factor-recovery-codes` | Ja | POST regeneriert Codes | Recovery-Code-Daten |
| POST | `/user/confirmed-two-factor-authentication` | Ja | Bestätigungscode | 2FA-Bestätigung |
| GET | `/user/passkeys/options` | Ja | Keine | WebAuthn-Registrierungsoptionen |
| POST | `/user/passkeys` | Ja | WebAuthn-Registrierungsantwort und Passkey-Name | Passkey registrieren |
| DELETE | `/user/passkeys/{passkey}` | Ja | Passkey-ID | Passkey löschen |
| GET | `/passkeys/login/options` | Nein | Keine | WebAuthn-Loginoptionen |
| POST | `/passkeys/login` | Nein | WebAuthn-Authentifizierungsantwort | Passkey-Login |
| GET | `/passkeys/confirm/options` | Ja | Keine | WebAuthn-Bestätigungsoptionen |
| POST | `/passkeys/confirm` | Ja | WebAuthn-Bestätigungsantwort | Passwortbestätigungs-/Passkey-Fluss |

## Teams und Vereinsverwaltung

| Methode | URI | Auth erforderlich | Eingabe / Pfadparameter | Antwort |
|---|---|---:|---|---|
| GET | `/mein-team` | Ja | Keine | Inertia `MeinTeam` |
| POST | `/verein/{team}/beitritt-bestaetigen` | Ja | `{team}` | Mitgliedschaft aktivieren, Redirect |
| GET / POST | `/verein-erstellen` | Ja, Rolle `trainer` | POST: `name` | Onboarding-Seite / Verein erstellen |
| POST | `/teams/{team}/logo` | Ja, aktives Vereins-Team | Multipart `logo` (JPG/PNG/WebP, max. 2 MiB) | Logo speichern, Redirect |
| POST | `/invitations/{invitation}/accept` | Ja | `{invitation}`-Code | Einladung annehmen, Redirect |
| DELETE | `/invitations/{invitation}` | Ja | `{invitation}`-Code | Einladung ablehnen, Redirect |
| GET / POST / DELETE | `/register/payment` | Ja | POST startet Checkout, DELETE bricht ausstehende Registrierung ab | Inertia `auth/Register`, Stripe-Redirect oder Redirect |
| GET | `/settings/teams` | Ja, verifiziert und Clubzugriff | Keine | Inertia `teams/Index` |
| POST | `/settings/teams` | Ja, verifiziert und Clubzugriff | `name` | Team erstellen, Redirect |
| GET / PATCH / DELETE | `/settings/teams/{team}` | Ja, verifiziert, Clubzugriff und Mitgliedschaft | PATCH: `name`; `{team}` ist Team-Slug | Inertia `teams/Edit`, speichern oder löschen |
| POST | `/settings/teams/{team}/switch` | Ja, verifiziert, Clubzugriff und Mitgliedschaft | `{team}` | Aktuelles Team wechseln |
| DELETE | `/settings/teams/{team}/leave` | Ja, verifiziert, Clubzugriff und Mitgliedschaft | `{team}` | Team verlassen |
| POST | `/settings/teams/{team}/members` | Ja, Teamzugriff und Berechtigung | `email` oder 8-stellige `public_id`, dazu `role` | Mitglied zur Bestätigung vormerken |
| PATCH | `/settings/teams/{team}/members/{user}` | Ja, Teamzugriff und Berechtigung | `role` | Teamrolle ändern |
| DELETE | `/settings/teams/{team}/members/{user}` | Ja, Teamzugriff und Berechtigung | `{user}` | Mitglied entfernen |
| POST | `/settings/teams/{team}/invitations` | Ja, Teamzugriff und Berechtigung | `email`, `role` | Einladung erstellen und zustellen |
| DELETE | `/settings/teams/{team}/invitations/{invitation}` | Ja, Teamzugriff und Berechtigung | `{team}`, `{invitation}` | Einladung stornieren |
| GET | `/settings/teams/{team}/payment/complete` | Ja | Query `session_id` | Zahlung prüfen und Tenant öffnen |
| GET / POST | `/verwaltung/teams` | Ja, verifiziert, Rolle `verwaltung` | POST: `name` | Admin-Liste / Mannschaft anlegen |
| PATCH | `/verwaltung/teams/{team}/trainer` | Ja, verifiziert, Rolle `verwaltung` | `trainer_id` oder `null` | Trainerzuweisung ändern |
| DELETE | `/verwaltung/teams/{team}` | Ja, verifiziert, Rolle `verwaltung` | `{team}` | Team löschen |
| GET / POST | `/verwaltung/mitglieder` | Ja, verifiziert, Rolle `verwaltung` | POST: `email`, `password` | Mitgliederliste / Mitglied anlegen |
| PATCH | `/verwaltung/mitglieder/{user}/rollen` | Ja, verifiziert, Rolle `verwaltung` | `roles` (Array) | Globale Rollen ändern |
| DELETE | `/verwaltung/mitglieder/{user}` | Ja, verifiziert, Rolle `verwaltung` | `{user}` | Mitglied löschen |

## Spiel- und Hallenpläne

| Methode | URI | Auth erforderlich | Eingabe / Beispiel | Antwort |
|---|---|---:|---|---|
| GET | `/spielplan` | Ja, Clubzugriff | Optional `season` | Inertia `Spielplan` |
| POST | `/spielplan/csv` | Ja, bearbeitbares Team | Multipart `file` (CSV/TXT, max. 2 MiB) | JSON-Vorschau: `rows`, `names`, `suggested`, `errors` |
| PUT | `/spielplan` | Ja, bearbeitbares Team | `team_id`, `season`, `rows[]` (Datum, Gegner, Heim/Auswärts, Ort) | Speichern, Toast und Redirect |
| GET | `/hallenplan` | Ja, Clubzugriff | Keine | Inertia `Hallenplan` |
| POST | `/hallenplan/csv` | Ja, bearbeitbares Team | Multipart `file` (CSV/TXT, max. 2 MiB) | JSON-Vorschau: `rows`, `errors` |
| PUT | `/hallenplan` | Ja, bearbeitbares Team | `rows[]` (Team, Halle, Wochentag, Beginn, Ende) | Speichern, Benachrichtigungen und Redirect |

Beispiel für einen validen Spielplan-Speicherkörper:

```json
{
  "team_id": 12,
  "season": "2026/27",
  "rows": [
    {
      "date": "2026-11-14",
      "versus": "Beispielverein",
      "at_home": true,
      "city": "Musterstadt"
    }
  ]
}
```

## Beispiele für JSON-Aktionen

### Suche

```http
GET /suche?q=spieler&limit=5
Accept: application/json
```

Beispielantwort (die konkrete Trefferliste hängt von Daten und Berechtigungen ab):

```json
{
  "query": "spieler",
  "groups": [
    {
      "type": "user",
      "label": "Benutzer",
      "items": [
        {
          "type": "user",
          "title": "Demo Spieler",
          "url": "<Route-URL>"
        }
      ]
    }
  ]
}
```

### CSV-Vorschau

```bash
curl -X POST https://lazytown.test/spielplan/csv \
  -H "X-CSRF-TOKEN: <Session-CSRF-Token>" \
  -b "<Session-Cookie>" \
  -F "file=@spielplan.csv"
```

Die Antwort enthält `rows` mit geparsten Spielzeilen (`date`, `kickoff_time`, `home`, `away`, `city`, `season`), `names`, `suggested` und `errors`. Die Hallenplanvorschau enthält `rows` mit `weekday`, `starts_at`, `ends_at`, `gym_name`, `team_id`, `team_name` sowie `errors`.

### Benutzerdatenexport

`GET /settings/profile/export` liefert einen JSON-Download mit folgender Struktur:

```json
{
  "exported_at": "<ISO-8601-Zeitstempel>",
  "profile": {
    "name": "<Name>",
    "birth_date": null,
    "city": null,
    "phone": null,
    "email": "<E-Mail>",
    "email_verified_at": null,
    "created_at": "<ISO-8601-Zeitstempel>"
  },
  "teams": [
    {
      "name": "<Teamname>",
      "slug": "<Team-Slug>"
    }
  ]
}
```

## Suche, Profil und Benachrichtigungen

| Methode | URI | Auth erforderlich | Eingabe / Beispiel | Antwort |
|---|---|---:|---|---|
| GET | `/suche` | Ja | Query `q` (max. 100 Zeichen), `limit` (1–10, Standard 10) | JSON `{ "query": "...", "groups": [...] }`; unter 2 Zeichen leere Gruppen |
| GET | `/profil` | Ja | Keine | Inertia `Profil` |
| GET / PATCH | `/settings/profile` | Ja | PATCH: `name`, `birth_date`, `city`, `phone`, `email` | Inertia-Seite / Redirect |
| DELETE | `/settings/profile` | Ja, verifiziert | Passwortbestätigung des Requests | User löschen, Redirect |
| GET | `/settings/profile/export` | Ja | Keine | JSON-Datei `lazytown-datenexport.json` |
| GET / PATCH | `/settings/notifications` | Ja | PATCH: `notification_settings` (Boolean-Map) | Seite / Redirect |
| POST | `/notifications/read` | Ja | Keine | Alle eigenen Einträge gelesen, zurück |
| POST | `/notifications/{notification}/read` | Ja | Eigene Notification-ID | Eintrag gelesen, zurück |
| DELETE | `/notifications/{notification}` | Ja | Eigene Notification-ID | Eintrag gelöscht, zurück |
| POST | `/notifications/test` | Ja, nur `local`/`testing` | Keine | Testnotification, zurück |

## Sicherheit, Anzeige und Hilfsrouten

| Methode | URI | Auth erforderlich | Eingabe | Antwort |
|---|---|---:|---|---|
| ANY | `/settings` | Ja | Keine | Redirect auf `/settings/profile` |
| GET | `/settings/security` | Ja, verifiziert, Passwortbestätigung, Clubzugriff | Keine | Inertia `settings/Security` |
| PUT | `/settings/password` | Ja, verifiziert, Clubzugriff | `current_password`, `password`, `password_confirmation` | Passwort aktualisieren, zurück |
| GET | `/settings/appearance` | Ja, verifiziert, Clubzugriff | Keine | Inertia `settings/Appearance` |
| GET | `/mitglied-profil-vervollstaendigen` | Ja | Keine | Inertia-Profilergänzung |
| PATCH | `/mitglied-profil-vervollstaendigen` | Ja, erzwungenes Onboarding | Profilfelder, `password`, `password_confirmation` | Speichern, Redirect |

Die Anfrage-/Antwortdetails einzelner Formularfelder ergeben sich aus den verlinkten Form Requests; die Tabellen sind keine versionierte REST-Spezifikation.

## Framework- und lokale Toolrouten

`php artisan route:list --only-vendor` enthält zusätzlich `/up` (Laravel Health), `/storage/{path}` (Filesystem), `_inertia/devtools/entries` (Inertia Devtools) und `_boost/browser-logs` (Laravel Boost). Diese Paket-/Betriebsrouten sind keine fachliche öffentliche API. Das Route-Inventar ist versionsabhängig; aktuelle Registrierung mit `php artisan route:list`.
