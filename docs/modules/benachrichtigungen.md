# Fachbereich: Benachrichtigungen

Benachrichtigungen werden überwiegend in der Laravel-Datenbank gespeichert und als gemeinsame Inertia-Props bereitgestellt. Benutzer können Einträge als gelesen markieren, löschen und einzelne Kategorien deaktivieren.

## Struktur und wichtige Dateien

| Datei | Aufgabe |
|---|---|
| [NotificationController.php](../../app/Http/Controllers/NotificationController.php) | Markiert Benachrichtigungen als gelesen, löscht sie und erzeugt eine lokale Testbenachrichtigung. |
| [NotificationDispatcher.php](../../app/Services/Notifications/NotificationDispatcher.php) | Prüft die Kategorieeinstellung des Benutzers, bevor eine Notification gesendet wird. |
| [ScheduleUpdateNotifier.php](../../app/Services/Notifications/ScheduleUpdateNotifier.php) | Sendet Planänderungen an aktive Teammitglieder mit Spielerrolle. |
| [ScheduleUpdate.php](../../app/Notifications/Plans/ScheduleUpdate.php) | Speichert Planänderungsdaten im Database-Channel. |
| [TeamInvitation.php](../../app/Notifications/Teams/TeamInvitation.php) | Versendet Einladungen per Mail und Datenbank; implementiert `ShouldQueue`. |
| [TestNotification.php](../../app/Notifications/TestNotification.php) | Lokale Testnotification für das Dropdown. |
| [HandleInertiaRequests.php](../../app/Http/Middleware/HandleInertiaRequests.php) | Teilt die zehn letzten Benachrichtigungen und den ungelesenen Zähler mit Inertia. |
| [config/notifications.php](../../config/notifications.php) | Definiert Kategorien `team_invitations`, `team_changes`, `schedule_updates` und `club_announcements`. |
| [notifications migration](../../database/migrations/2026_10_08_085804_create_notifications_table.php) | Erstellt polymorphe Benachrichtigungsdatensätze. |

## Routen

| Methode | URI | Controller@Action | Middleware |
|---|---|---|---|
| POST | `/notifications/read` | `NotificationController@markAllAsRead` | web, auth |
| POST | `/notifications/{notification}/read` | `NotificationController@markAsRead` | web, auth |
| DELETE | `/notifications/{notification}` | `NotificationController@destroy` | web, auth |
| POST | `/notifications/test` | `NotificationController@createTest` | web, auth; nur local/testing |
| GET / PATCH | `/settings/notifications` | `NotificationSettingsController@edit/update` | web, auth |

## Datenmodell, Abhängigkeiten und Oberfläche

Laravel speichert Benachrichtigungen in `notifications` mit UUID, Typ, polymorphem Empfänger, JSON-ähnlichem Nutzdatenfeld, `read_at` und Zeitstempeln. Der eingeloggte User wird im Controller als Eigentümer-Scope verwendet. Team-Einladungen und Planänderungen sind die im Anwendungscode konkret implementierten Fachdaten.

Die Oberfläche ist in `resources/js/components/` in Navbar-/Dropdown-Bausteinen eingebunden; Einstellungspräferenzen liegen in [pages/settings/Notifications.vue](../../resources/js/pages/settings/Notifications.vue). Ein separates Benachrichtigungsseiten-Routing oder API Resource ist nicht vorhanden.

Die Notification-Tests stehen in [NotificationsTest.php](../../tests/Feature/NotificationsTest.php), [TeamInvitationTest.php](../../tests/Feature/Teams/TeamInvitationTest.php) und [NotificationSettingsTest.php](../../tests/Feature/Settings/NotificationSettingsTest.php).

Es gibt keine eigenen Event- oder Listener-Klassen. `TeamInvitation` ist eine gequeue-te Notification, kein Job in `app/Jobs/`; der Queue-Worker muss für die asynchrone Mail-/Datenbankzustellung betrieblich laufen.

