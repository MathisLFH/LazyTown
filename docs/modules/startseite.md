# Fachbereich: Startseite und öffentliche Seiten

Die Startseite zeigt anonymen Besuchern eine Willkommensseite und angemeldeten Benutzern einen tenant- und teambezogenen Überblick. Öffentliche Informationsseiten werden direkt als Inertia-Seiten gerendert.

## Struktur und wichtige Dateien

| Datei | Aufgabe |
|---|---|
| [StartseiteController.php](../../app/Http/Controllers/StartseiteController.php) | Leitet Vereins-Onboarding/Zahlung weiter und bereitet anstehende Spiele, Trainings, Nachrichten und offene Einladungen auf. |
| [web.php](../../routes/web.php) | Definiert Home-, Dashboard- und Informationsseitenrouten. |
| [HandleInertiaRequests.php](../../app/Http/Middleware/HandleInertiaRequests.php) | Ergänzt seitenübergreifend Benutzer-, Team-, Tenant- und Notification-Props. |
| [app.ts](../../resources/js/app.ts) | Initialisiert Inertia und wählt Layouts für App-, Auth-, Kontakt- und Einstellungsseiten. |
| [Welcome.vue](../../resources/js/pages/Welcome.vue), [Startseite.vue](../../resources/js/pages/Startseite.vue) | Anonyme Landingpage und angemeldetes Dashboard. |
| [ContactLayout.vue](../../resources/js/layouts/ContactLayout.vue) | Layout der Kontakt-/Informationsseiten. |

## Routen

| Methode | URI | Controller@Action | Middleware |
|---|---|---|---|
| GET | `/` | `StartseiteController@__invoke` | web |
| GET | `/startseite` | `StartseiteController@__invoke` | web |
| GET | `/contact/about-us` | Inertia-Seite `AboutUs` | web |
| GET | `/contact/kontakt` | Inertia-Seite `Kontakt` | web |
| GET | `/contact/impressum` | Inertia-Seite `Impressum` | web |
| GET | `/contact/datenschutzerklaerung` | Inertia-Seite `Datenschutzerklaerung` | web |
| GET | `/.well-known/passkey-endpoints` | Closure in `routes/settings.php` | web |

Die Startseite ist öffentlich erreichbar; der Controller gibt bei anonymen Requests `Welcome` zurück. Ein angemeldeter Trainer ohne aktiven Vereinsbeitritt kann zum Onboarding umgeleitet werden; ein Tenant mit ausstehender Registrierung wird zum Zahlungsschritt geleitet.

## Datenmodell und Abhängigkeiten

Der Dashboard-Controller liest über Eloquent aus `User`, `Membership`, `TeamInvitation`, `ClubMatch`, `Training`, `Gym` und `Message`. Die Auswahl der Termine und Nachrichten ist durch aktives Team und Tenant eingeschränkt. Bei fehlenden Terminen oder Nachrichten liefert der Controller leere Collections statt Beispieldaten.

Die Seiten verwenden die gemeinsamen Layouts und Props des Inertia-Middleware-Stacks. Öffentliche Kontaktseiten haben keine eigenen Modelle, Services oder Requests.

## Tests

- [StartseiteTest.php](../../tests/Feature/StartseiteTest.php)
- [DashboardTest.php](../../tests/Feature/DashboardTest.php)
- [PlaceholderPagesTest.php](../../tests/Feature/PlaceholderPagesTest.php)

Für diesen Bereich existieren keine eigenen Jobs, Events/Listener, Policies oder Eloquent Resources.

