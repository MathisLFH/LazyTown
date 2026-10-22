# Tests und Qualitätssicherung

Das Backend wird mit Pest/PHPUnit getestet. `phpunit.xml` definiert die Suites `Unit` und `Feature` und konfiguriert SQLite `:memory:` für den Testlauf. Frontend-Tests sind nicht eingerichtet.

## Teststruktur

| Bereich | Vorhandene Tests |
|---|---|
| Allgemein / Dashboard | [StartseiteTest.php](../tests/Feature/StartseiteTest.php), [DashboardTest.php](../tests/Feature/DashboardTest.php), [PlaceholderPagesTest.php](../tests/Feature/PlaceholderPagesTest.php) |
| Auth | [AuthenticationTest.php](../tests/Feature/Auth/AuthenticationTest.php), [RegistrationTest.php](../tests/Feature/Auth/RegistrationTest.php), [PasswordResetTest.php](../tests/Feature/Auth/PasswordResetTest.php), [EmailVerificationTest.php](../tests/Feature/Auth/EmailVerificationTest.php), [VerificationNotificationTest.php](../tests/Feature/Auth/VerificationNotificationTest.php), [TwoFactorChallengeTest.php](../tests/Feature/Auth/TwoFactorChallengeTest.php), [PasswordConfirmationTest.php](../tests/Feature/Auth/PasswordConfirmationTest.php), [MemberProfileCompletionTest.php](../tests/Feature/MemberProfileCompletionTest.php) |
| Teams und Zahlungen | [TeamTest.php](../tests/Feature/Teams/TeamTest.php), [TeamMemberTest.php](../tests/Feature/Teams/TeamMemberTest.php), [TeamInvitationTest.php](../tests/Feature/Teams/TeamInvitationTest.php), [TeamLogoTest.php](../tests/Feature/Teams/TeamLogoTest.php), [TeamPaymentTest.php](../tests/Feature/Teams/TeamPaymentTest.php), [PruneExpiredTeamInvitationsTest.php](../tests/Feature/Teams/PruneExpiredTeamInvitationsTest.php), [TeamAdministrationTest.php](../tests/Feature/TeamAdministrationTest.php), [AdminMemberManagementTest.php](../tests/Feature/AdminMemberManagementTest.php) |
| Pläne | [MatchPlanTest.php](../tests/Feature/Plans/MatchPlanTest.php), [TrainingPlanTest.php](../tests/Feature/Plans/TrainingPlanTest.php) |
| Suche / Benachrichtigungen | [SearchSuggestionsTest.php](../tests/Feature/SearchSuggestionsTest.php), [NotificationsTest.php](../tests/Feature/NotificationsTest.php), [NotificationSettingsTest.php](../tests/Feature/Settings/NotificationSettingsTest.php) |
| Einstellungen / Daten | [ProfileUpdateTest.php](../tests/Feature/Settings/ProfileUpdateTest.php), [SecurityTest.php](../tests/Feature/Settings/SecurityTest.php), [ErDiagramModelsTest.php](../tests/Feature/ErDiagramModelsTest.php), [DemoAccountsSeederTest.php](../tests/Feature/DemoAccountsSeederTest.php) |
| Basis / Unit | [ExampleTest.php](../tests/Feature/ExampleTest.php), [ExampleTest.php](../tests/Unit/ExampleTest.php), [Pest.php](../tests/Pest.php), [TestCase.php](../tests/TestCase.php) |

## Ausführen

```powershell
php artisan test
```

Einen einzelnen Bereich gezielt ausführen:

```powershell
php artisan test tests/Feature/Teams/TeamInvitationTest.php
```

Composer-Skripte:

```powershell
composer test
composer ci:check
```

`composer test` leert den Config-Cache, führt Pint-Prüfung und PHPStan aus und startet danach die Tests. `composer ci:check` ergänzt ESLint, Prettier-Prüfung und `vue-tsc`; dies ist der CI-Qualitätslauf.

## Testumgebung und Coverage

- `phpunit.xml` setzt DB auf SQLite im Speicher, Cache/Session auf `array`, Queue auf `sync`, Mail auf `array` und Broadcast auf `null`.
- Der GitHub-CI-Workflow führt zusätzlich gegen einen temporären PostgreSQL-16-Service aus.
- Testdaten sollen vorhandene Factories und Seeder verwenden; Demo-Konten sind ausschließlich für `local` und `testing` zulässig.
- In `phpunit.xml` ist kein Coverage-Report konfiguriert; im CI wird Coverage deaktiviert. Es gibt keine Frontend-Coverage-Konfiguration.

