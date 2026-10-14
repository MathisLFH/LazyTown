# Datenplan: Spielplan & Hallenplan (Trainer bearbeiten, Spieler lesen)

Branch: `feat/data`. Lokale DB wurde per `php artisan migrate` auf Branch-Stand gebracht (4 ausstehende Migrationen eingespielt, inkl. `gyms.tenant_id`).

---

## 1. Ist-Analyse (verifiziert)

### 1.1 Vorhandene Tabellen (nur **eine** kleine Migration nötig: `matches.kickoff_time`, siehe 4.1)

| Tabelle | Relevante Spalten | Modell |
|---|---|---|
| `tenants` | id, name, subdomain, admin_id | `Tenant` (= **Verein**) |
| `teams` | id, **tenant_id**, name, slug, is_personal, soft deletes | `Team` (= **Mannschaft**; `is_personal=true` ist ein privates Pseudo-Team und zählt nicht) |
| `team_members` | team_id, user_id, role (`owner`/`admin`/`member`), status (`active`/…), tenant_id (**in Daten oft NULL → nie darauf verlassen**) | `Membership` |
| `users` | tenant_id, `roles` (JSON-Array: `spieler`/`trainer`/`verwaltung`), active_role | `User::hasRole('trainer')` |
| `matches` | id, **tenant_id**, **team_id**, season (string), date, **kickoff_time (NEU, nullable `time`)**, versus (string), at_home (bool), city (nullable string) | `ClubMatch` (Table-Name `matches`, Casts date/bool) |
| `trainings` | id, **tenant_id**, **team_id**, gym_id (nullable), weekday (string), starts_at (time), ends_at (time) | `Training` |
| `gyms` | id, **tenant_id (nullable)**, name | `Gym` |

Factories existieren bereits: `ClubMatchFactory`, `TrainingFactory`, `GymFactory` (Gym-Default `tenant_id = null`).

### 1.2 Ergebnis zur Frage „Sind Hallen vereinsübergreifend?“ → **Teilweise, ja**

- `gyms.tenant_id` ist **nullable** (Migration `2026_10_12_133851`), `GymFactory` setzt `null`.
  - `tenant_id = NULL` → globale/gemeinsame Halle, für alle Vereine sichtbar.
  - `tenant_id = X` → Halle gehört nur Verein X.
- `trainings.tenant_id` und `matches.tenant_id` sind **NOT NULL** mit `cascadeOnDelete` → **Pläne sind immer vereinsgebunden**.
- Konsequenz: Hallen sind ein gemeinsamer Katalog, aber der **Hallenplan (Belegung) ist pro Verein**. Nutzer sehen nur Trainings ihres Vereins; in der Hallenauswahl erscheinen `tenant_id = eigener Verein` **oder** `NULL`.
- Fremde Vereine dürfen **nicht** sehen, wer eine globale Halle belegt (Datenschutz). Ein „Belegt von anderem Verein“-Hinweis ist bewusst **out of scope**.

### 1.3 Wichtige Beobachtungen im Code

- `routes/web.php`: `spielplan` und `hallenplan` sind aktuell `Route::inertia(...)` innerhalb `club.access` → werden durch Controller ersetzt.
- `resources/js/components/Schedule/SchedulePageContent.vue` und `HallPlan/HallPlanPageContent.vue` + Composables `useGameplan.ts` / `useGymnasiumPlan.ts` enthalten **nur Mock-Daten** (und kaputte Umlaute `Ã¤`). Werden ersetzt.
- Die aktive Rolle (`useAuth.ts`) liegt nur in `localStorage` → **reine UI-Sache, nie für Berechtigungen nutzen**. Berechtigung ausschließlich serverseitig.
- Middleware `role:trainer` (`EnsureUserRole`) existiert und liefert die 403-Seite `errors/PermissionDenied`.
- Toasts: `Inertia::flash('toast', ['type' => 'success', 'message' => ...])` (siehe `TeamController`).
- Es gibt **kein** CSV-Paket und **kein** Vitest → keine neuen Dependencies; CSV wird serverseitig mit PHP geparst (Pest-testbar).
- Testdaten haben 3 Vereine namens „LazyTown“ → immer über `tenant_id` scopen, nie über Namen.

### 1.4 Die CSV „Dummy Mathis.csv“

- Die Beispiel-CSV enthält **keine Uhrzeit**. Der Parser akzeptiert zusätzlich eine **optionale** Spalte `Uhrzeit` (`18:30` oder `18.30`, auch `18:30 Uhr`); fehlt sie, bleibt `kickoff_time = NULL` und der Trainer trägt Zeiten im Editor nach.
- Spalten: `Datum;Mannschaft 1;Mannschaft 2;Ort;Saison`, Trenner `;`, **Encoding Windows-1252** (ß/ü/ö = 1 Byte, kein UTF-8!), Datum `dd.mm.yy` (`12.09.26`), Saison `2026/27`.
- Enthält eine **ligaweite** Tabelle: 72 Spiele, 9 Mannschaften, jede 16× (8 Heim / 8 Auswärts). Der Verein braucht daraus nur die Zeilen **seiner** Mannschaft.
- `Mannschaft 1` = Heimmannschaft, `Ort` = Spielort (PLZ + Stadt des Heimteams). Doppelspieltage kommen vor (gleiches Datum, gleiche Heimmannschaft, anderer Gegner).

**Mapping CSV → `matches`** (für gewählte eigene Mannschaft-Bezeichnung `X` aus der CSV):

| CSV-Zeile | team_id | versus | at_home | city | date | season |
|---|---|---|---|---|---|---|
| `Mannschaft 1 == X` | gewähltes Team | `Mannschaft 2` | `true` | `Ort` | ISO aus `dd.mm.yy` (`yy` → 20yy) | `Saison` |
| `Mannschaft 2 == X` | gewähltes Team | `Mannschaft 1` | `false` | `Ort` | wie oben | wie oben |
| sonst | – wird ignoriert (Zähler im UI: „56 Spiele anderer Mannschaften ignoriert“) | | | | | |

`kickoff_time` = Spalte `Uhrzeit` falls vorhanden, sonst `NULL`. `tenant_id` wird **immer serverseitig** gesetzt (aus `team.tenant_id`), nie vom Client.

**Hallenplan-CSV (neues, einfaches Format, `;`, UTF-8 oder Windows-1252):**
`Wochentag;Beginn;Ende;Halle;Mannschaft` → z. B. `Dienstag;18:00;19:30;Sporthalle Nord;Herren 1`. `Mannschaft` optional, wenn der Trainer nur ein Team verwaltet. Wochentag deutsch (`Montag`…`Sonntag`, case-insensitive, `Mo`/`Di`… akzeptieren), Zeit `H:i`.

---

## 2. Rechte & Sichtbarkeit

Definitionen (zentral in `App\Concerns\HasTeams`, siehe 4.1):

- **Vereins-Teams des Users**: `teams()` mit `is_personal = false` und Pivot `status = active` (identisch zu `EnsureClubAccess`).
- **Tenant des Users**: `tenant_id` des aktuellen Vereins-Teams (`current_team_id`, sonst erstes aktives Vereins-Team). Ist `tenant_id` NULL → leere Pläne, kein Fehler (`PlaceholderPagesTest` nutzt Teams ohne Tenant).
- **Sichtbare Teams** (Lesen): alle nicht-persönlichen Teams **desselben Tenants**.
- **Bearbeitbare Teams** (Schreiben): Vereins-Teams des Users mit Pivot-`role ∈ {owner, admin}` **und** `hasRole('trainer')`.
- `canEdit` (Prop ans Frontend) = bearbeitbare Teams nicht leer. Nur zur Anzeige – Server prüft bei jedem Schreibzugriff erneut.

| Aktion | Spieler / Verwaltung (ohne Trainerrolle) | Trainer |
|---|---|---|
| Spielplan/Hallenplan ansehen (eigener Verein) | ✅ | ✅ |
| CSV hochladen, bearbeiten, speichern | ❌ 403 | ✅ nur für bearbeitbare Teams |
| Plan anderer Vereine | ❌ (Daten werden nie geladen) | ❌ |

Standardansicht für Spieler: Team-Filter **vorausgewählt auf eigene Teams**, per Chip „Ganzer Verein“ erweiterbar.

---

## 3. Routen (`routes/web.php`, Gruppe `club.access` anpassen)

```php
Route::get('spielplan', [MatchPlanController::class, 'show'])->name('spielplan');
Route::get('hallenplan', [TrainingPlanController::class, 'show'])->name('hallenplan');

Route::middleware('role:trainer')->group(function () {
    Route::post('spielplan/csv', [MatchPlanController::class, 'parseCsv'])->name('spielplan.csv');       // nur Vorschau, kein DB-Write
    Route::put('spielplan', [MatchPlanController::class, 'sync'])->name('spielplan.sync');               // speichert den Entwurf
    Route::post('hallenplan/csv', [TrainingPlanController::class, 'parseCsv'])->name('hallenplan.csv');
    Route::put('hallenplan', [TrainingPlanController::class, 'sync'])->name('hallenplan.sync');
});
```

Routennamen `spielplan`/`hallenplan` bleiben → `Navbar.vue` und `PlaceholderPagesTest` laufen unverändert. Wayfinder-Funktionen kommen danach aus `@/actions/App/Http/Controllers/Plans/...` (Vite-Plugin generiert automatisch).

**Warum ein einziger `sync`-Endpoint statt CRUD pro Zeile?** Der Trainer bearbeitet einen Entwurf im Browser (Import, Hinzufügen, Entfernen, Ändern) und speichert **einmal** atomar. Das ist die benutzerfreundlichste Strategie (siehe 6) und spart Routen/Dateien: CSV-Import und manuelle Bearbeitung nutzen denselben Speicherpfad und dieselbe Validierung.

---

## 4. Backend – Dateien und Inhalt

### 4.0 Uhrzeit für Spiele (neu)
- **Neue Migration** (`php artisan make:migration add_kickoff_time_to_matches_table --table=matches --no-interaction`): `$table->time('kickoff_time')->nullable()->after('date');`, `down()`: `dropColumn('kickoff_time')`. Danach `php artisan migrate`.
- **`app/Models/ClubMatch.php`**: `'kickoff_time'` in `#[Fillable]` ergänzen (kein Cast; Wert wird beim Ausgeben auf `H:i` gekürzt, weil Postgres `HH:MM:SS` liefert).
- **`database/factories/ClubMatchFactory.php`**: `'kickoff_time' => fake()->optional()->time('H:i')`.
- Uhrzeit ist **optional** (viele Spiele sind noch ohne Zeit); Anzeige ohne Zeit: „Uhrzeit offen“ bzw. nur Datum.
- Sortierung überall: `orderBy('date')->orderByRaw('kickoff_time IS NULL')->orderBy('kickoff_time')->orderBy('id')`.

### 4.1 Bestehende Datei ändern
**`app/Concerns/HasTeams.php`** – zwei kleine Methoden (wird bereits von `User` genutzt):
- `clubTeamsForPlans(): Collection<Team>` → alle nicht-persönlichen Teams des Tenants des Users (`Team::where('tenant_id', $tenantId)->where('is_personal', false)->orderBy('name')`).
- `manageableTeamsForPlans(): Collection<Team>` → Schnittmenge aus `clubTeamsForPlans()` und eigenen aktiven Memberships mit `role ∈ [owner, admin]`; leer, wenn `! hasRole('trainer')`.
Begründung: Rechte-Logik an einer Stelle, beide Controller + Tests nutzen sie.

**`routes/web.php`** – siehe Abschnitt 3 (Imports der beiden Controller ergänzen).

### 4.2 Neue Dateien

1. **`app/Services/Plans/PlanCsvParser.php`** – gemeinsame CSV-Logik:
   - `readRows(UploadedFile $file): array` → Zeilen als assoziative Arrays mit normalisierten Headern.
   - Encoding: BOM entfernen; wenn `! mb_check_encoding($raw, 'UTF-8')` → `mb_convert_encoding($raw, 'UTF-8', 'Windows-1252')` (**zwingend**, sonst kaputte Umlaute bei der Beispiel-CSV).
   - Delimiter automatisch: `;` bevorzugt, sonst `,`. Header trim + lowercase.
   - `parseDate(string): ?string` → akzeptiert `d.m.y`, `d.m.Y`, `Y-m-d`; gibt `Y-m-d` zurück (`yy` → 2000+yy).
   - `parseKickoffTime(?string): ?string` → akzeptiert `H:i`, `H.i`, `H:i Uhr`, `H` (`9` → `09:00`); ungültig → Zeilenfehler, leer → `null`.
   - `parseTime(string): ?string` → `H:i`; `parseWeekday(string): ?string` → deutsche Normalform.
   - Limits: max. 2 MB, max. 1000 Zeilen.
   - Fehler pro Zeile sammeln `[['line' => 5, 'message' => 'Ungültiges Datum']]`, defekte Zeilen überspringen, nie hart abbrechen.

2. **`app/Http/Controllers/Plans/MatchPlanController.php`**
   - `show(Request)`: Query-Params `season` (optional). Props (Inertia `Plans/…` siehe 5): 
     - `teams`: `[{id, name, canEdit}]` (sichtbare Teams)
     - `seasons`: distinct `season` der Tenant-Matches, absteigend
     - `season`: gewählte Saison (Default: Saison des nächsten kommenden Spiels, sonst neueste)
     - `matches`: `[{id, team_id, date: 'Y-m-d', kickoff_time: 'H:i'|null, versus, at_home, city, season}]` – **nur** `where('tenant_id', $tenantId)`, `where('season', $season)`, `whereIn('team_id', sichtbare Team-IDs)`, Sortierung laut 4.0, mit `select` der nötigen Spalten
     - `canEdit`: bool
   - `parseCsv(ParseCsvRequest)`: gibt `{rows: [{date, kickoff_time|null, home, away, city, season}], names: [distinct Mannschaftsnamen sortiert], suggested: {team_id => name}, errors: [...]}` als **JSON** zurück (`response()->json`, kein DB-Zugriff). `suggested` = pro bearbeitbarem Team die CSV-Bezeichnung mit gleichem normalisiertem Namen (lowercase, Umlaute/ß vereinfacht, Mehrfach-Leerzeichen entfernt) oder `null`. Die Zuordnung „eigene Mannschaft → Zeilen → `versus/at_home`“ macht das Frontend rein clientseitig (Tabelle in 1.4) für sofortige Vorschau ohne Roundtrip.
   - `sync(SaveMatchPlanRequest)`: `DB::transaction`: (1) Team ∈ bearbeitbare Teams, sonst `abort(403)`; (2) vorhandene Matches von `team_id`+`season` laden; (3) nicht mehr enthaltene IDs löschen; (4) Zeilen mit `id` updaten (id **muss** zu diesem Team+Season gehören, sonst 422); (5) neue Zeilen anlegen mit `tenant_id = $team->tenant_id`. Danach `Inertia::flash('toast', ['type' => 'success', 'message' => 'Spielplan gespeichert.'])` und `to_route('spielplan', ['season' => $season])`.

3. **`app/Http/Controllers/Plans/TrainingPlanController.php`** – analog:
   - `show`: Props `teams`, `gyms` (`Gym::where(fn($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))->orderBy('name')` → `[{id, name, shared: bool}]`), `trainings` (`with` vermeiden, direkt `gym_id` + Name mappen; `starts_at`/`ends_at` als `H:i`; sortiert nach Wochentag-Reihenfolge Mo→So, dann `starts_at`), `canEdit`.
   - `parseCsv`: Zeilen `{weekday, starts_at, ends_at, gym_name, team_id|null, team_name}`; `Mannschaft` wird case-insensitiv gegen **bearbeitbare** Teams aufgelöst; unbekannt → Zeilenfehler; fehlt die Spalte und gibt es genau 1 bearbeitbares Team → dieses.
   - `sync`: Transaktion; Scope = alle Trainings der **bearbeitbaren** Teams des Tenants; nicht enthaltene IDs löschen; Update/Create; Halle auflösen über `gym_name`: case-insensitiver Treffer in (tenant-eigene ∪ globale) Hallen, sonst `Gym::create(['tenant_id' => $tenantId, 'name' => …])`. Globale Hallen werden **nie** verändert. Trainings anderer Teams (nicht bearbeitbar) bleiben unberührt.

4. **`app/Http/Requests/Plans/ParseCsvRequest.php`** – `file` => `required|file|mimes:csv,txt|max:2048` (+ `authorize()` = `manageableTeamsForPlans()->isNotEmpty()`). Wird von beiden `parseCsv`-Methoden genutzt.

5. **`app/Http/Requests/Plans/SaveMatchPlanRequest.php`**
   ```php
   'team_id' => ['required','integer'],
   'season'  => ['required','regex:/^\d{4}\/\d{2,4}$/'],
   'rows'    => ['present','array','max:200'],
   'rows.*.id'      => ['nullable','integer'],
   'rows.*.date'    => ['required','date_format:Y-m-d'],
   'rows.*.kickoff_time' => ['nullable','date_format:H:i'],
   'rows.*.versus'  => ['required','string','max:255'],
   'rows.*.at_home' => ['required','boolean'],
   'rows.*.city'    => ['nullable','string','max:255'],
   ```
   `authorize()`: Team ∈ `manageableTeamsForPlans()`. Gleiche Zeile (date+versus+at_home) doppelt → Validierungsfehler pro Zeile (verhindert Doppelimporte).

6. **`app/Http/Requests/Plans/SaveTrainingPlanRequest.php`**
   ```php
   'rows'               => ['present','array','max:200'],
   'rows.*.id'          => ['nullable','integer'],
   'rows.*.team_id'     => ['required','integer'],   // muss in manageableTeamsForPlans() sein
   'rows.*.gym_name'    => ['required','string','max:255'],
   'rows.*.weekday'     => ['required', Rule::in(['Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'])],
   'rows.*.starts_at'   => ['required','date_format:H:i'],
   'rows.*.ends_at'     => ['required','date_format:H:i','after:rows.*.starts_at'],
   ```
   Überschneidungen in derselben Halle am selben Tag sind **Warnung im UI, kein Server-Fehler** (Hallen können Teilbereiche haben).

### 4.3 Seeder (optional, nur Dev)
In `DatabaseSeeder` nichts erzwingen; Testdaten über Factories (`ClubMatch::factory()`, `Training::factory()`). Kein Commit der Download-CSV.

---

## 5. Frontend – Dateien und Inhalt

Alle Texte deutsch, Tailwind v4 + vorhandene `components/ui/*` (button, card, badge, dialog, select, input, skeleton, sonner), Icons aus `@lucide/vue`. Skill `inertia-vue-development` und `tailwindcss-development` beachten; Vue-Komponenten mit **einem** Root-Element.

### 5.1 Bestehende Dateien ersetzen (Mock entfernen)
- **`resources/js/pages/Spielplan.vue`** – bekommt die Props vom Controller (`defineProps`) und reicht sie an `SchedulePageContent` durch.
- **`resources/js/pages/Hallenplan.vue`** – analog.
- **`resources/js/components/Schedule/SchedulePageContent.vue`** – komplett neu (Spielplan-Ansicht + Editor, siehe 6).
- **`resources/js/components/HallPlan/HallPlanPageContent.vue`** – komplett neu (Wochenansicht + Editor).
- **`resources/js/composables/useGameplan.ts`** – Mock-Daten raus; enthält nur Typen (`MatchRow` mit `kickoff_time: string | null`) und reine Hilfsfunktionen: `groupByMonth`, `nextMatch`, `formatDate` (`Sa, 12.09.`), `mapCsvToMatches(rows, ownName, teamId, season)` (Tabelle 1.4).
- **`resources/js/composables/useGymnasiumPlan.ts`** – Mock raus; Typen (`TrainingRow`), `weekDays`, `findConflicts(rows)` (gleiche Halle+Tag, überlappende Zeit), `groupByWeekday`.
  (Wiederverwendung der beiden vorhandenen Composables statt neuer Dateien.)

### 5.2 Neue Komponenten (nur 2, gemeinsam genutzt)
- **`resources/js/components/Plans/PlanToolbar.vue`** – Kopfzeile: Titel, Filter, rechts Trainer-Buttons `Plan bearbeiten` / `CSV importieren` / `Exportieren` (nur wenn `canEdit`). Im Bearbeitungsmodus ersetzt durch `Verwerfen` + `Speichern (n Änderungen)`.
- **`resources/js/components/Plans/CsvImportDialog.vue`** – Dialog (3 Schritte in einer Ansicht): 
  1. Datei wählen/drag & drop (+ Link „Vorlage herunterladen“),
  2. Zuordnung: Select „Welche Mannschaft in der CSV ist deine?“ (vorbelegt mit `suggested`) und Ziel-Team (nur wenn Trainer >1 Team verwaltet), Modus `Ersetzen` (Default) / `Hinzufügen`,
  3. Vorschau-Tabelle (max. 8 Zeilen) + Zusammenfassung („16 Spiele übernommen · 56 anderer Mannschaften ignoriert · 0 Fehler“) + fehlerhafte Zeilen aufklappbar. Button **„In Entwurf übernehmen“** (schreibt **nichts** in die DB).
  Für Hallenplan: Prop `type="training"` blendet Schritt 2 aus bzw. zeigt nur Modus.

Upload via `fetch`/`useHttp` (Inertia v3) mit `FormData` gegen `spielplan.csv`/`hallenplan.csv`; CSRF läuft über das Cookie-Handling von Inertia/Laravel.

---

## 6. UX-Konzept (die „beste Strategie“)

**Entwurfsmodell: Ansehen → „Plan bearbeiten“ → Entwurf → einmal speichern.**

1. Alle sehen dieselbe ruhige Leseansicht. Trainer haben rechts oben **[Plan bearbeiten]** (primär) und **[CSV importieren]** (sekundär, Icon `Upload`). Spieler sehen diese Buttons nicht.
2. **Plan bearbeiten** schaltet die Seite in den Bearbeitungsmodus: die Zeilen werden zu Inline-Feldern (Datum `type=date`, Gegner Text, Heim/Auswärts Segment-Toggle, Ort Text). Pro Zeile ein `🗑`-Button (Zeile wird nur im Entwurf markiert/durchgestrichen, „Rückgängig“ möglich). Oben **[+ Spiel hinzufügen]**; `Enter` im letzten Feld legt eine neue Zeile an. Bearbeitbare Mannschaft/Saison per Select im Kopf (nur bearbeitbare Teams).
3. **CSV importieren** öffnet den Dialog (5.2). Nach „In Entwurf übernehmen“ landet man direkt im Bearbeitungsmodus, neue Zeilen **grün markiert**, bei `Ersetzen` werden alte Zeilen **rot/durchgestrichen** angezeigt → Trainer sieht exakt, was passiert.
4. Fixe untere Leiste (sticky): `„12 neu · 3 geändert · 2 gelöscht“ — [Verwerfen] [Speichern]`. Speichern = ein `router.put(sync)`; bei Erfolg Toast + zurück zur Leseansicht; bei Validierungsfehlern werden die betroffenen Zeilen rot mit Meldung markiert (Fehlerschlüssel `rows.3.date` → Zeile 3).
5. Schutz vor Datenverlust: `beforeunload` + Inertia `router.on('before')` fragen bei ungespeichertem Entwurf nach.
6. Extras mit wenig Aufwand: **Exportieren** (Blob-Download der aktuellen Liste als CSV `;`, UTF-8 mit BOM für Excel; Spalten `Datum;Uhrzeit;Mannschaft 1;Mannschaft 2;Ort;Saison`, also re-importierbar), **Vorlage herunterladen**.

Hallenplan identisch, nur mit Feldern Wochentag, Beginn, Ende, Halle (Combobox/Select mit vorhandenen Hallen + „Neue Halle: …“ als Freitext), Mannschaft (Select, nur bearbeitbare). Konflikte (Halle+Tag überlappend) → gelbes Warn-Icon `TriangleAlert` mit Tooltip, blockiert nicht.

---

## 7. UI-Spezifikation (kompakt, ruhig)

### Spielplan (Lesen)
- Kopf: `Spielplan` · Select Saison · Chips der Teams (Default: eigene Teams, Chip „Ganzer Verein“) · Filter `Alle | Heim | Auswärts` · Toggle „Vergangene ausblenden“.
- **Karte „Nächstes Spiel“** oben (Datum groß, „in 3 Tagen“, Gegner, Heim/Auswärts-Badge, Ort).
- Darunter Liste gruppiert nach Monat (sticky Monatsüberschrift „September 2026“). Zeile: Datum-Chip `Sa 12.09.` · **Gegner** (`vs`/`@`) · Badge `Heim` (grün) / `Auswärts` (grau) · Ort rechts (auf Mobil unter dem Gegner). Teamname nur anzeigen, wenn mehrere Teams sichtbar.
- Details: Klick auf Zeile klappt Detailbereich auf (Mannschaft, Gegner, Uhrzeit, Ort, Saison, „Zum Kalender hinzufügen“ ist **out of scope**). Vergangene Spiele `opacity-60`; Heute hervorgehoben.
- **Uhrzeit**: In der Zeile direkt neben dem Datum-Chip (`Sa 12.09. · 15:00`); ohne Zeit nur Datum, im Detailbereich „Uhrzeit: noch offen“. „Nächstes Spiel“-Karte zeigt `Sa, 12.09.2026 · 15:00 Uhr`. Vergangen/Heute wird mit Datum **und** Uhrzeit verglichen (Spiel heute später am Tag zählt noch als „nächstes“).
- **Uhrzeit im Editor**: `<input type="time">` (Feld klein, `w-24`), optional; Tastatur-Schnellwahl nicht nötig. Bei einer neuen Zeile wird die Uhrzeit der letzten Zeile vorbelegt (Spieltage haben meist gleiche Zeit). Zusätzlich Aktion „Uhrzeit für alle Spiele ohne Zeit setzen“ im Menü `…` der Toolbar (ein Prompt, füllt nur leere Felder).
- Desktop: kompakte Tabellenzeilen (`py-2`); Mobil: Karten, einspaltig.

### Hallenplan (Lesen)
- Kopf: `Hallenplan` · Filter Halle · Filter Mannschaft.
- Wochenraster **Mo–So** (nur Wochentage mit Einträgen + immer Mo–Fr): je Tag eine Spalte mit gestapelten Kacheln `18:00–19:30 · Herren 1 · Sporthalle Nord`, nach Zeit sortiert, Farbpunkt je Team (stabile Farbe aus Team-ID). Mobil: Tage untereinander als Akkordeon/Abschnitte. Heutiger Tag hervorgehoben.

### Zustände & Polish (Pflicht für „voll ready“)
- **Ladezustand**: `Skeleton`-Zeilen (Inertia navigiert serverseitig → `<Deferred>` nicht nötig, aber Skeleton beim CSV-Parsing und beim Speichern: Button `Spinner` + disabled).
- **Leerzustände**: Spieler: „Noch kein Spielplan hinterlegt. Dein Trainer kann ihn hier hochladen.“ Trainer: gleicher Text + große Buttons `CSV hochladen` / `Spiel hinzufügen`.
- **Fehlerzustände**: Dateifehler (zu groß, kein CSV, 0 passende Zeilen: „Keine Spiele für ‚X‘ gefunden – wähle eine andere Mannschaft“), Zeilenfehler im Dialog.
- Dark-Mode über bestehende Tokens (`bg-background`, `border-border`, `text-muted-foreground`); keine festen Farben außer Status (grün/gelb/rot mit `dark:`-Varianten).
- Barrierefreiheit: Labels an allen Inputs, Fokus-Ringe, Dialog mit Fokus-Falle (shadcn-vue Dialog), Heim/Auswärts nicht nur durch Farbe (Text im Badge), Löschen mit `aria-label`.
- Umlaute prüfen (alte Dateien enthielten `Ã¤` → Datei als UTF-8 speichern).
- Deutsches Datumsformat überall via `Intl.DateTimeFormat('de-DE')`; DB-Werte bleiben ISO.
- Wayfinder statt hartcodierter URLs; keine neuen NPM-Pakete.

---

## 8. Tests (Pest, `php artisan make:test --pest Plans/…`, Skill `testing-best-practices` lesen)

**`tests/Feature/Plans/MatchPlanTest.php`**
- Spieler sieht nur Matches des eigenen Tenants (zweiter Tenant mit eigenem Match → nicht in Props); `canEdit=false`.
- Spieler: `PUT spielplan.sync` und `POST spielplan.csv` → 403.
- Trainer (Owner/Admin-Membership, Rolle `trainer`): `sync` legt neue an, aktualisiert, löscht fehlende; `tenant_id` kommt vom Team (nicht vom Request).
- Trainer darf nicht in Team eines anderen Tenants / Team ohne eigene Membership schreiben (403) und keine fremde `id` updaten (422).
- `parseCsv` mit Fixture der Beispiel-CSV in **Windows-1252** (`tests/Fixtures/plans/spielplan_cp1252.csv`, gekürzt auf ~6 Zeilen): Umlaute korrekt, `dd.mm.yy` → ISO, `names` korrekt, fehlerhafte Zeile landet in `errors`.
- Validierung: ungültiges Datum, doppelte Zeile, ungültige Uhrzeit (`25:99`) → 422; `kickoff_time` = null erlaubt; gespeicherte Zeit wird als `H:i` zurückgegeben (nicht `H:i:s`).
- `parseCsv` mit optionaler Spalte `Uhrzeit` (`18:30`, `18.30`, `18:30 Uhr`) und ohne die Spalte (→ `null`).
- Sortierung: Spiele am selben Tag nach Uhrzeit, Spiele ohne Uhrzeit am Tagesende.

**`tests/Feature/Plans/TrainingPlanTest.php`**
- Sichtbarkeit: eigene Trainings + Hallenliste enthält globale (`tenant_id` NULL) und eigene, **nicht** die Hallen anderer Vereine.
- Spieler 403 beim Schreiben; Trainer `sync` mit neuer Halle legt tenant-eigene `Gym` an, nutzt vorhandene/globale per Namen, ändert globale nie.
- `ends_at` vor `starts_at` → 422; Trainings nicht bearbeitbarer Teams bleiben beim Sync unberührt.

Bestehend weiterhin grün halten: `tests/Feature/PlaceholderPagesTest.php` (Team ohne Tenant → leere Props, Status 200).

---

## 9. Umsetzungsreihenfolge (für kleines Modell, jeweils prüfen)

1. Migration `kickoff_time` + `ClubMatch`/`ClubMatchFactory` anpassen, `php artisan migrate`, `tests/Feature/ErDiagramModelsTest.php` weiter grün.
2. `HasTeams`: beide Helper + Pest-Test dafür (kurz in den Plan-Tests mit abdecken).
3. `PlanCsvParser` (inkl. `parseKickoffTime`) + `ParseCsvRequest` + Fixture; Parser-Tests grün.
4. `MatchPlanController` (`show`, `parseCsv`, `sync`) + `SaveMatchPlanRequest` + Routen; `php artisan test --compact tests/Feature/Plans/MatchPlanTest.php`.
5. `TrainingPlanController` + `SaveTrainingPlanRequest`; Training-Tests grün.
6. Frontend Lesen: `Spielplan.vue`/`Hallenplan.vue` + Content-Komponenten + Composables (nur Anzeige, Spieler-Sicht, inkl. Uhrzeit).
7. `PlanToolbar.vue`, Bearbeitungsmodus (inkl. Uhrzeitfeld), `CsvImportDialog.vue`, Speichern, Fehleranzeige, Unsaved-Guard.
8. Polish-Checkliste (Abschnitt 7), Dark Mode, Mobil (375 px) im Browser prüfen.
9. `vendor/bin/pint --dirty --format agent`, `php artisan test --compact` (Plan-Tests + `PlaceholderPagesTest`), `npm run build` (bzw. Typecheck), dann Nutzer bitten, die komplette Suite zu laufen.

---

## 10. Bewusst nicht enthalten / offene Punkte

- Keine weiteren Schemaänderungen außer `matches.kickoff_time`. Ein Status (verlegt/abgesagt) wäre eine spätere Erweiterung.
- Tenant-übergreifende Hallenbelegung/Konfliktanzeige (Datenschutz) – nicht umgesetzt.
- **Entschieden:** Die Rolle **Verwaltung** darf Pläne vorerst **nicht** bearbeiten (nur `trainer`). Spätere Öffnung = eine Zeile in `manageableTeamsForPlans()`.
- Merken der CSV-Teamzuordnung (z. B. „VfL Löningen III“ = Team X) würde eine neue Spalte brauchen → aktuell Vorbelegung per Namensähnlichkeit + manuelle Auswahl.
- Echte „Aktive Rolle“-Umschaltung ist nur clientseitig (`localStorage`); Berechtigungen hängen daran **nicht**.
