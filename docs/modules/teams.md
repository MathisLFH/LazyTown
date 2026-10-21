# Fachbereich: Teams und Vereinsverwaltung

Dieser Fachbereich verwaltet Tenants (Vereine), Teams/Mannschaften, Mitgliedschaften, Einladungen, administrative Rollen und Vereinszahlungen. `Team` ist im Code eine Mannschaft; `Tenant` ist der Verein und wird über eine Subdomain aufgelöst.

## Struktur und wichtige Dateien

| Datei | Aufgabe |
|---|---|
| [TeamController.php](../../app/Http/Controllers/Teams/TeamController.php) | Teamlisten, Bearbeitung, Teamwechsel, Mitgliedsseite, Logo und Löschung. |
| [TeamMemberController.php](../../app/Http/Controllers/Teams/TeamMemberController.php) | Mitglieder hinzufügen, Beitritt bestätigen, Teamrolle ändern oder Mitglied entfernen. |
| [TeamInvitationController.php](../../app/Http/Controllers/Teams/TeamInvitationController.php) | Einladungen erstellen, stornieren, annehmen und ablehnen. |
| [TeamAdministrationController.php](../../app/Http/Controllers/Teams/TeamAdministrationController.php) | Vereinsweite Mannschaftsverwaltung und Trainerzuordnung. |
| [MemberAdministrationController.php](../../app/Http/Controllers/Teams/MemberAdministrationController.php) | Mitglieder eines Tenants verwalten und Anmeldekonten anlegen. |
| [ClubOnboardingController.php](../../app/Http/Controllers/Teams/ClubOnboardingController.php) | Vereins-Onboarding durch Trainer. |
| [RegistrationPaymentController.php](../../app/Http/Controllers/Teams/RegistrationPaymentController.php) | Registrierungs-Checkout und Abbruch ausstehender Anmeldung. |
| [StripeWebhookController.php](../../app/Http/Controllers/Teams/StripeWebhookController.php) | Verifiziert den Stripe-Signature-Header und verarbeitet Checkout-Webhooks. |
| [TeamPaymentController.php](../../app/Http/Controllers/Teams/TeamPaymentController.php) | Prüft den Checkout-Abschluss und bestätigt eine Zahlung. |
| [CreateTeam.php](../../app/Actions/Teams/CreateTeam.php) | Erstellt ein Team transaktional und synchronisiert optional Owner/Berechtigungen. |
| [DeleteTeam.php](../../app/Actions/Teams/DeleteTeam.php) | Entfernt Team, Einladungen und Mitgliedschaften mit Fallback-Teambehandlung. |
| [HandleStripeCheckoutWebhook.php](../../app/Actions/Teams/HandleStripeCheckoutWebhook.php) | Prüft relevante Checkout-Events und ordnet sie Team sowie Zahlendem zu. |
| [StripeCheckoutService.php](../../app/Services/Teams/StripeCheckoutService.php) | Erstellt/prüft Stripe-Test-Checkout-Sessions und Webhook-Signaturen. |
| [TeamPermissionSynchronizer.php](../../app/Services/Teams/TeamPermissionSynchronizer.php) | Synchronisiert Teamrollen und Berechtigungen mit Spatie Laravel Permission. |
| [Team.php](../../app/Models/Team.php), [Tenant.php](../../app/Models/Tenant.php), [Membership.php](../../app/Models/Membership.php), [TeamInvitation.php](../../app/Models/TeamInvitation.php) | Eloquent-Modelle für Verein, Mannschaft, Teammitgliedschaft und Einladung. |
| [TeamPolicy.php](../../app/Policies/TeamPolicy.php) | Autorisiert Lesen, Ändern, Löschen, Einladen und Mitgliedsverwaltung. |
| [TeamRole.php](../../app/Enums/TeamRole.php), [TeamPermission.php](../../app/Enums/TeamPermission.php), [UserRole.php](../../app/Enums/UserRole.php) | Definieren Teamrollen, Berechtigungsnamen und globale Rollen. |

Requests liegen unter `app/Http/Requests/Teams/` und `app/Http/Requests/Settings/`. Wiederverwendbare Teamlogik liegt unter `app/Concerns/HasTeams.php`. Es gibt keine Team-Eloquent Resources.

## Routen

| Methode | URI | Controller@Action | Middleware |
|---|---|---|---|
| GET | `/mein-team` | `TeamController@meinTeam` | web, auth, club.access |
| POST | `/verein/{team}/beitritt-bestaetigen` | `TeamMemberController@confirm` | web, auth |
| GET / POST | `/verein-erstellen` | `ClubOnboardingController@create/store` | web, auth, role:trainer |
| POST | `/teams/{team}/logo` | `TeamController@updateLogo` | web, auth, club.access |
| POST | `/invitations/{invitation}/accept` | `TeamInvitationController@accept` | web, auth |
| DELETE | `/invitations/{invitation}` | `TeamInvitationController@decline` | web, auth |
| GET / POST / DELETE | `/register/payment` | `RegistrationPaymentController@show/store/destroy` | web, auth |
| GET | `/settings/teams` | `TeamController@index` | web, auth, verified, club.access |
| POST | `/settings/teams` | `TeamController@store` | web, auth, verified, club.access |
| GET / PATCH / DELETE | `/settings/teams/{team}` | `TeamController@edit/update/destroy` | web, auth, verified, club.access, EnsureTeamMembership |
| POST | `/settings/teams/{team}/switch` | `TeamController@switch` | web, auth, verified, club.access, EnsureTeamMembership |
| DELETE | `/settings/teams/{team}/leave` | `TeamController@leave` | web, auth, verified, club.access, EnsureTeamMembership |
| POST | `/settings/teams/{team}/members` | `TeamMemberController@store` | web, auth, verified, club.access, EnsureTeamMembership |
| PATCH | `/settings/teams/{team}/members/{user}` | `TeamMemberController@update` | web, auth, verified, club.access, EnsureTeamMembership |
| DELETE | `/settings/teams/{team}/members/{user}` | `TeamMemberController@destroy` | web, auth, verified, club.access, EnsureTeamMembership |
| POST | `/settings/teams/{team}/invitations` | `TeamInvitationController@store` | web, auth, verified, club.access, EnsureTeamMembership |
| DELETE | `/settings/teams/{team}/invitations/{invitation}` | `TeamInvitationController@destroy` | web, auth, verified, club.access, EnsureTeamMembership |
| GET | `/settings/teams/{team}/payment/complete` | `TeamPaymentController@complete` | web, auth |
| GET / POST | `/verwaltung/teams` | `TeamAdministrationController@index/store` | web, auth, verified, role:verwaltung |
| PATCH | `/verwaltung/teams/{team}/trainer` | `TeamAdministrationController@updateTrainer` | web, auth, verified, role:verwaltung |
| DELETE | `/verwaltung/teams/{team}` | `TeamController@destroy` | web, auth, verified, role:verwaltung |
| GET / POST | `/verwaltung/mitglieder` | `MemberAdministrationController@index/store` | web, auth, verified, role:verwaltung |
| PATCH | `/verwaltung/mitglieder/{user}/rollen` | `MemberAdministrationController@updateRoles` | web, auth, verified, role:verwaltung |
| DELETE | `/verwaltung/mitglieder/{user}` | `MemberAdministrationController@destroy` | web, auth, verified, role:verwaltung |
| POST | `/stripe/webhook` | `StripeWebhookController@__invoke` | web; CSRF-Ausnahme, Stripe-Signatur erforderlich |

Die vollständige Route-Namensliste und zusätzliche Endpunkte stehen in [api.md](../api.md).

## Datenmodell

- Ein `Tenant` besitzt Benutzer, Teams, Trainings, Spiele, Nachrichten und Subscriptions.
- Ein `Team` gehört optional zu einem Tenant und enthält Benutzer über `team_members`; der Pivot `Membership` trägt Rolle, Status und optionale Tenant-/Permission-Rollen-IDs.
- Ein Team hat Einladungen, Spiele und Trainings. Team- und Mitglieds-IDs sind in der Datenbank verknüpft.
- `TeamInvitation` gehört zu einem Team und zum einladenden User; der Code ist der Route-Key.
- `Tenant` und `Subscription` sind über `tenant_subscriptions` verbunden.
- `roles` ist die Spatie-Tabelle und wird zugleich vom `ClubRole`-Modell adressiert. Sie ist nicht dasselbe wie `users.roles` (JSON-Rollen für Spieler/Trainer/Verwaltung).

Das vollständige Schema und die Fremdschlüssel stehen in [database.md](../database.md).

## Abhängigkeiten, Seiten und Tests

Der Bereich verwendet Authentifizierung und Tenant-Middleware, Spatie-Permissions, Stripe sowie Benachrichtigungen für Einladungen. Vue-Seiten liegen in `resources/js/pages/teams/`, `resources/js/pages/admin/`, außerdem `MeinTeam.vue`, `Profil.vue` und Registrierungsseiten unter `pages/auth/`. Wiederverwendbare UI-Komponenten liegen unter `resources/js/components/Teams/` und `components/Onboarding/`.

Tests:

- [TeamTest.php](../../tests/Feature/Teams/TeamTest.php)
- [TeamMemberTest.php](../../tests/Feature/Teams/TeamMemberTest.php)
- [TeamInvitationTest.php](../../tests/Feature/Teams/TeamInvitationTest.php)
- [TeamLogoTest.php](../../tests/Feature/Teams/TeamLogoTest.php)
- [TeamPaymentTest.php](../../tests/Feature/Teams/TeamPaymentTest.php)
- [PruneExpiredTeamInvitationsTest.php](../../tests/Feature/Teams/PruneExpiredTeamInvitationsTest.php)
- [TeamAdministrationTest.php](../../tests/Feature/TeamAdministrationTest.php)
- [AdminMemberManagementTest.php](../../tests/Feature/AdminMemberManagementTest.php)

Es gibt keine eigenen Team-Event-/Listener-Klassen oder Jobs. Die Einladungs-Mail/DB-Benachrichtigung ist eine `ShouldQueue`-Laravel-Notification; die Queue-Verarbeitung muss betrieblich gestartet werden.
