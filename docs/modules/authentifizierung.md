# Fachbereich: Authentifizierung

Dieser Fachbereich bindet Laravel Fortify und Laravel Passkeys an Vue-/Inertia-Seiten an. Er umfasst Registrierung, Login, Passwortwiederherstellung, Verifizierung, 2FA, Passkeys und die Vervollständigung neu angelegter Mitgliederprofile.

## Struktur und wichtige Dateien

| Datei | Aufgabe |
|---|---|
| [FortifyServiceProvider.php](../../app/Providers/FortifyServiceProvider.php) | Registriert benutzerdefinierte Fortify-Actions, Inertia-Ansichten, Antwortverträge und Rate Limits. |
| [CreateNewUser.php](../../app/Actions/Fortify/CreateNewUser.php) | Legt normale Spieler und Vereins-Administratoren samt Tenant, Team und Subscription an. |
| [ResetUserPassword.php](../../app/Actions/Fortify/ResetUserPassword.php) | Implementiert das projektspezifische Zurücksetzen von Passwörtern. |
| [CompleteMemberProfileRequest.php](../../app/Http/Requests/Auth/CompleteMemberProfileRequest.php) | Validiert das erzwungene Profil-/Passwort-Onboarding eines Mitglieds. |
| [MemberProfileCompletionController.php](../../app/Http/Controllers/Auth/MemberProfileCompletionController.php) | Zeigt und speichert die Profilvervollständigung. |
| [LoginResponse.php](../../app/Http/Responses/LoginResponse.php) | Definiert das projektspezifische Verhalten nach dem Login. |
| [PasskeyLoginResponse.php](../../app/Http/Responses/PasskeyLoginResponse.php) | Definiert die Antwort nach Passkey-Anmeldung. |
| [TwoFactorLoginResponse.php](../../app/Http/Responses/TwoFactorLoginResponse.php) | Definiert die Antwort nach erfolgreicher 2FA. |
| [RegisterResponse.php](../../app/Http/Responses/RegisterResponse.php) | Leitet die Registrierung in den vorgesehenen nächsten Anwendungsschritt. |
| [VerifyEmailResponse.php](../../app/Http/Responses/VerifyEmailResponse.php) | Definiert die Antwort nach E-Mail-Verifizierung. |
| [User.php](../../app/Models/User.php) | Authentifizierbares Modell mit Fortify-2FA- und Passkey-Fähigkeiten, Rollen und Profilfeldern. |
| [EnsureInitialPasswordChanged.php](../../app/Http/Middleware/EnsureInitialPasswordChanged.php) | Leitet Benutzer mit initialem Passwort zur Profilvervollständigung um. |
| [Fortify-Konfiguration](../../config/fortify.php) | Aktiviert Registrierung, Passwort-Reset, E-Mail-Verifikation, bestätigte 2FA und Passkeys. |

Die Vue-Seiten liegen in `resources/js/pages/auth/`: `Login.vue`, `Register.vue`, `ForgotPassword.vue`, `ResetPassword.vue`, `VerifyEmail.vue`, `TwoFactorChallenge.vue`, `ConfirmPassword.vue` und `CompleteMemberProfile.vue`. Der gemeinsame Einstieg und Layoutwahl stehen in [resources/js/app.ts](../../resources/js/app.ts).

## Routen

Fortify und Passkeys registrieren die Auth-Routen. Sie verwenden `web`-Middleware; einzelne geschützte Operationen verlangen angemeldete Benutzer beziehungsweise eine Passwortbestätigung.

| Methode | URI | Controller@Action | Middleware / Schutz |
|---|---|---|---|
| GET | `/login` | Fortify `AuthenticatedSessionController@create` | web |
| POST | `/login` | Fortify `AuthenticatedSessionController@store` | web, Login-Limit |
| POST | `/logout` | Fortify `AuthenticatedSessionController@destroy` | web, auth |
| GET / POST | `/register` | Fortify `RegisteredUserController@create/store` | web; erstellt normale oder Vereinsregistrierung |
| GET / POST | `/forgot-password` | Fortify `PasswordResetLinkController@create/store` | web |
| GET / POST | `/reset-password/{token}` / `/reset-password` | Fortify `NewPasswordController@create/store` | web |
| GET | `/email/verify` | Fortify `EmailVerificationPromptController` | web, auth |
| GET | `/email/verify/{id}/{hash}` | Fortify `VerifyEmailController@__invoke` | web, auth, signierte Verifikation |
| POST | `/email/verification-notification` | Fortify `EmailVerificationNotificationController` | web, auth, Throttle |
| GET / POST | `/user/confirm-password` | Fortify `ConfirmablePasswordController@show/store` | web, auth |
| GET | `/user/confirmed-password-status` | Fortify `ConfirmedPasswordStatusController` | web, auth |
| GET / POST | `/two-factor-challenge` | Fortify `TwoFactorAuthenticatedSessionController@create/store` | web, 2FA-Limit |
| POST / DELETE | `/user/two-factor-authentication` | Fortify `TwoFactorAuthenticationController@store/destroy` | web, auth, Passwortbestätigung gemäß Feature |
| GET | `/user/two-factor-secret-key` | Fortify `TwoFactorSecretKeyController@show` | web, auth |
| GET | `/user/two-factor-qr-code` | Fortify `TwoFactorQrCodeController@show` | web, auth |
| GET / POST | `/user/two-factor-recovery-codes` | Fortify `RecoveryCodeController@index/store` | web, auth |
| POST | `/user/confirmed-two-factor-authentication` | Fortify `ConfirmedTwoFactorAuthenticationController@store` | web, auth |
| GET | `/user/passkeys/options` | Passkeys `PasskeyRegistrationController@index` | web, auth |
| POST | `/user/passkeys` | Passkeys `PasskeyRegistrationController@store` | web, auth |
| DELETE | `/user/passkeys/{passkey}` | Passkeys `PasskeyRegistrationController@destroy` | web, auth |
| GET / POST | `/passkeys/login/options`, `/passkeys/login` | Passkeys `PasskeyLoginController@index/store` | web, Passkey-Limit |
| GET / POST | `/passkeys/confirm/options`, `/passkeys/confirm` | Passkeys `PasskeyConfirmationController@index/store` | web, Fortify-Passwortbestätigung |
| GET / PATCH | `/mitglied-profil-vervollstaendigen` | `MemberProfileCompletionController@edit/update` | web, auth |

`route:list --only-vendor` enthält außerdem interne Inertia-Devtools-, Boost- und Health-Routen. Diese sind keine eigenen fachlichen Auth-Endpunkte.

## Daten und Beziehungen

- `User` ist das zentrale Auth-Modell und gehört optional zu einem `Tenant`; Teammitgliedschaften werden über `team_members` verbunden.
- `passkeys` speichert Credential-JSON und gehört per `user_id` zu `users`.
- 2FA-Felder liegen direkt in `users`; Reset-Token und Sessions liegen in `password_reset_tokens` und `sessions`.
- Die vollständige Spaltenübersicht steht in [database.md](../database.md).

## Abhängigkeiten und Abgrenzungen

Die Registrierung ruft `CreateTeam` auf und kann `Tenant`, `Team` und `Subscription` anlegen. Team-Einladungen werden im Auth-Kontext angezeigt. Benutzerrollen stammen aus dem Anwendungsmodell; die Vereins-/Team-Berechtigungen sind im [Teams-Fachbereich](./teams.md) beschrieben.

Im Composer-Manifest gibt es kein Socialite-Paket und keine Social-Login-Routen. Die frühere Root-README-Aussage „Social authentication ready“ ist nicht durch den aktuellen Code belegt.

## Tests

- [AuthenticationTest.php](../../tests/Feature/Auth/AuthenticationTest.php)
- [RegistrationTest.php](../../tests/Feature/Auth/RegistrationTest.php)
- [PasswordResetTest.php](../../tests/Feature/Auth/PasswordResetTest.php)
- [EmailVerificationTest.php](../../tests/Feature/Auth/EmailVerificationTest.php)
- [VerificationNotificationTest.php](../../tests/Feature/Auth/VerificationNotificationTest.php)
- [TwoFactorChallengeTest.php](../../tests/Feature/Auth/TwoFactorChallengeTest.php)
- [PasswordConfirmationTest.php](../../tests/Feature/Auth/PasswordConfirmationTest.php)
- [MemberProfileCompletionTest.php](../../tests/Feature/MemberProfileCompletionTest.php)

## Nicht vorhandene Modulbestandteile

Es gibt keine eigenen Auth-Events, Listener, Jobs oder Eloquent Resources im Anwendungscode. Fortify/Passkeys registrieren die Auth-Routen und internen Paketdienste.

