# Projektdokumentation

Diese Dokumentation beschreibt die Anwendung anhand des aktuellen Repository-Codes und der Konfiguration. Sie richtet sich an neue Teammitglieder, Projektprüfer und Personen, die Entwicklung oder Betrieb nachvollziehen möchten.

## Inhaltsverzeichnis

| Thema | Dokument |
|---|---|
| Gesamtüberblick, Architektur und Diagramme | [architecture.md](./architecture.md) |
| Lokale Voraussetzungen und Installation | [setup.md](./setup.md) |
| Verzeichnis- und Dateistruktur | [project-structure.md](./project-structure.md) |
| Backend-Lifecycle und Laravel-Konzepte | [backend.md](./backend.md) |
| Vue-/Inertia-Frontend und Build | [frontend.md](./frontend.md) |
| Datenmodell, Tabellen und Migrationen | [database.md](./database.md) |
| Authentifizierung, Rollen und Rechte | [auth.md](./auth.md) |
| HTTP-Routen und JSON-Aktionen | [api.md](./api.md) |
| Teststrategie und Testbefehle | [testing.md](./testing.md) |
| CI, Build und Produktion | [deployment.md](./deployment.md) |
| Fachbegriffe | [glossary.md](./glossary.md) |
| Fachbereiche (anstelle technischer Laravel-Module) | [modules/README.md](./modules/README.md) |
| Mitarbeit und Hinzufügen eines Fachbereichs | [../CONTRIBUTING.md](../CONTRIBUTING.md) |

## Warum Fachbereiche statt Module dokumentiert werden

Das Projekt verwendet keine modulare Laravel-Erweiterung und enthält weder ein `Modules/`-Verzeichnis noch separate Modul-Service-Provider oder Modul-Routen. Die Anwendung ist als Laravel-Monolith organisiert: Controller, Requests, Modelle, Actions, Services und Vue-Seiten liegen in gemeinsam genutzten Projektverzeichnissen und sind fachlich gruppiert. Deshalb bezeichnet „Fachbereich“ in dieser Dokumentation eine Codegruppe, nicht ein unabhängig ladbares Laravel-Modul. Die einzelnen Beschreibungen und ihre Grenzen stehen in [modules/README.md](./modules/README.md).

Eine zweite Abweichung betrifft die API-Dokumentation: Es gibt keine `routes/api.php`-Datei. [api.md](./api.md) inventarisiert deshalb die tatsächlich registrierten Web-/Inertia-Routen sowie die durch Fortify und Passkeys ergänzten HTTP-Endpunkte und kennzeichnet JSON-Aktionen separat.

## Quellen und Genauigkeit

Routen wurden mit `php artisan route:list --except-vendor -v` und `--only-vendor` geprüft. Paketversionen stammen aus den Composer- und npm-Abhängigkeiten; Datenbanktabellen und Beziehungen aus Migrationen und Eloquent-Modellen. Wo ein Verhalten in einer älteren Notiz behauptet, im aktuellen Code aber nicht belegt wird, hat der Code Vorrang.

## Offene TODOs

- **TODO: bitte prüfen:** [Datenplan.md](../Datenplan.md) nennt `matches.kickoff_time`; aktuelle Migrationen und das Modell `ClubMatch` enthalten diese Spalte nicht. Entscheiden, ob die Notiz veraltet ist oder die Funktion noch umgesetzt werden soll. <!-- TODO: bitte prüfen: Datenplan und aktuelle Schemaquelle widersprechen sich bezüglich matches.kickoff_time. -->
- **TODO: bitte prüfen:** [Suchplan.md](../Suchplan.md) und [MULTI_TENANCY_IMPLEMENTATION_PLAN.md](../MULTI_TENANCY_IMPLEMENTATION_PLAN.md) beschreiben vor der heutigen Implementierung liegende Zustände. Falls diese Notizen weiterhin als Projektentscheidungen gelten, mit dem Ist-Code abgleichen und aktualisieren. <!-- TODO: bitte prüfen: Historische Such- und Multi-Tenancy-Planungsnotizen gegen den aktuellen Code abgleichen. -->
- **TODO: bitte prüfen:** Für Commit-Nachrichten ist im Repository keine verbindliche Konvention konfiguriert oder dokumentiert. Teamstandard festlegen, falls erforderlich. <!-- TODO: bitte prüfen: Commit-Konvention fehlt im Repository. -->
