# Fachbereich: Spiel- und Hallenpläne

Der Fachbereich verwaltet den Spielplan einer Mannschaft und Trainingszeiten in Hallen. Trainer und berechtigte Vereinsverwalter können Pläne bearbeiten; die Speicherung wird serverseitig validiert und transaktional ausgeführt.

## Struktur und wichtige Dateien

| Datei | Aufgabe |
|---|---|
| [MatchPlanController.php](../../app/Http/Controllers/Plans/MatchPlanController.php) | Lädt Spielplandaten, parst Spiele-CSV als JSON und speichert einen Saisonplan. |
| [TrainingPlanController.php](../../app/Http/Controllers/Plans/TrainingPlanController.php) | Lädt Trainingsdaten, parst Hallenplan-CSV und synchronisiert Trainings/Hallen. |
| [PlanCsvParser.php](../../app/Services/Plans/PlanCsvParser.php) | Normalisiert Encoding, Trennzeichen, Überschriften, Datum, Uhrzeit, Wochentag und Namen. |
| [SaveMatchPlanRequest.php](../../app/Http/Requests/Plans/SaveMatchPlanRequest.php) | Prüft Saison, Team-Zugriff, Spielzeilen und Duplikate. |
| [SaveTrainingPlanRequest.php](../../app/Http/Requests/Plans/SaveTrainingPlanRequest.php) | Prüft Trainingseinträge, bearbeitbare Teams und Zeitintervalle. |
| [ParseCsvRequest.php](../../app/Http/Requests/Plans/ParseCsvRequest.php) | Beschränkt CSV-Uploads auf `csv`/`txt` bis 2 MiB und prüft Berechtigung. |
| [ClubMatch.php](../../app/Models/ClubMatch.php), [Training.php](../../app/Models/Training.php), [Gym.php](../../app/Models/Gym.php) | Eloquent-Modelle für Spiele, Training und Hallenorte. |
| [ScheduleUpdateNotifier.php](../../app/Services/Notifications/ScheduleUpdateNotifier.php) | Benachrichtigt aktive Teammitglieder nach tatsächlichen Planänderungen. |
| [useGameplan.ts](../../resources/js/composables/useGameplan.ts), [useGymnasiumPlan.ts](../../resources/js/composables/useGymnasiumPlan.ts) | Kapseln Frontend-Daten und Formlogik der beiden Pläne. |
| [CsvImportDialog.vue](../../resources/js/components/Plans/CsvImportDialog.vue) | Upload, Vorschau, Fehleranzeige und Übernahme der CSV-Zeilen. |

## Routen

| Methode | URI | Controller@Action | Middleware |
|---|---|---|---|
| GET | `/spielplan` | `MatchPlanController@show` | web, auth, club.access |
| POST | `/spielplan/csv` | `MatchPlanController@parseCsv` | web, auth, club.access |
| PUT | `/spielplan` | `MatchPlanController@sync` | web, auth, club.access |
| GET | `/hallenplan` | `TrainingPlanController@show` | web, auth, club.access |
| POST | `/hallenplan/csv` | `TrainingPlanController@parseCsv` | web, auth, club.access |
| PUT | `/hallenplan` | `TrainingPlanController@sync` | web, auth, club.access |

Die Form Requests prüfen zusätzlich, ob der Benutzer das betreffende Team verwalten darf. Bei POST-CSV sind `file` (CSV/TXT, maximal 2048 KiB) erforderlich.

## Datenmodell

- `matches` (`ClubMatch`) gehört zu `Tenant` und `Team`; die Match-Spalten sind Saison, Datum, Gegner, Heim/Auswärts und optional Ort.
- `trainings` gehört zu `Tenant` und `Team`, optional zu `Gym`.
- `gyms` kann einem Tenant gehören oder mit `tenant_id = NULL` als gemeinsamer Hallenort verwendet werden.
- Bei jeder Pläneingabe wird der Tenant aus serverseitig zugelassenen Teams abgeleitet; es wird kein `tenant_id` aus dem Request übernommen.

## Abhängigkeiten, Seiten und Tests

Die Seiten sind [Spielplan.vue](../../resources/js/pages/Spielplan.vue) und [Hallenplan.vue](../../resources/js/pages/Hallenplan.vue). Die Speicherrouten nutzen `Team`-/`User`-Berechtigungen; bei Änderungen versendet der Notification-Service Datenbankbenachrichtigungen an aktive Teammitglieder mit Spieler-Mitgliedsrolle.

- [MatchPlanTest.php](../../tests/Feature/Plans/MatchPlanTest.php)
- [TrainingPlanTest.php](../../tests/Feature/Plans/TrainingPlanTest.php)

Es gibt keine Plans-Repositorys, Resources, Events oder Jobs. Das CSV-Parsen und Speichern ist Controller-/Service- und Request-Logik; die Queue wird für Planänderungsbenachrichtigungen nicht explizit genutzt.

