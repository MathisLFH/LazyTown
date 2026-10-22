# Fachbereich: Suche

Die Suche liefert kurze, nach Typ gruppierte Vorschläge im Kontext des angemeldeten Benutzers. Laravel Scout ist vorhanden; `config/scout.php` verwendet standardmäßig den Database-Treiber und deaktiviert Queue-Synchronisierung.

## Struktur und wichtige Dateien

| Datei | Aufgabe |
|---|---|
| [SearchController.php](../../app/Http/Controllers/SearchController.php) | Validiert und begrenzt Suchanfragen und liefert JSON. |
| [SearchSuggestionsRequest.php](../../app/Http/Requests/SearchSuggestionsRequest.php) | Erlaubt `q` bis 100 Zeichen und `limit` von 1 bis 10; verlangt Anmeldung. |
| [SearchService.php](../../app/Search/SearchService.php) | Fragt Provider nacheinander ab, begrenzt die Gesamttrefferzahl und gruppiert Ergebnisse. |
| [SearchContext.php](../../app/Search/SearchContext.php) | Übergibt User, Tenant und aktuelles Team an Provider. |
| [SearchProvider.php](../../app/Search/SearchProvider.php) | Gemeinsame Provider-Schnittstelle. |
| [SearchSuggestion.php](../../app/Search/SearchSuggestion.php) | Einheitliche Vorschlagsdarstellung. |
| [Providers](../../app/Search/Providers) | Provider für Benutzer, Teams, Hallenorte und Settings. |
| [scout.php](../../config/scout.php) | Standardtreiber `database`, keine Scout-Queue und Engine-Einstellungen. |

## Route

| Methode | URI | Controller@Action | Middleware |
|---|---|---|---|
| GET | `/suche?q={query}&limit={n}` | `SearchController@__invoke` | web, auth, throttle:search |

Die Suchroute ist mit 60 Anfragen pro Minute je Benutzer-ID/IP limitiert. `q` ist optional, maximal 100 Zeichen; `limit` ist optional, 1 bis 10 (Standard 10). Abfragen mit weniger als zwei Zeichen liefern leere Gruppen.

## Datenmodell, Abhängigkeiten und Oberfläche

Die Provider nutzen unter anderem `User`, `Team` und `Gym`; `User`, `Team` und `Gym` implementieren Scout `Searchable`. Ergebnisse werden anhand des aktuellen Users/Tenants/Teams kontextualisiert. Der Vertrag des JSON-Ergebnisses ist `{ query, groups }`; einzelne Gruppen enthalten Typ, Label und Vorschläge.

Das Suchfeld ist in der globalen Navigation eingebunden. Die Seite enthält keine eigenständige Suchergebnisroute; Frontend- und Requestdetails stehen in [frontend.md](../frontend.md) und [api.md](../api.md).

Test: [SearchSuggestionsTest.php](../../tests/Feature/SearchSuggestionsTest.php).

Es gibt keine Search-Events, Listener, Jobs oder Eloquent Resources. Scout-Index-Synchronisierung ist laut Konfiguration synchron und verwendet standardmäßig die Datenbank-Engine.

