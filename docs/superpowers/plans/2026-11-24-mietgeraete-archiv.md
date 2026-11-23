# Mietgeräte-Archiv und "Erneut mieten" Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** In der Liste "Mietgeräte" stehen nur aktuelle Geräte; Geräte mit ausschließlich abgeschlossenen Mietvorgängen liegen in einem zugeklappten Archiv und lassen sich mit einem Klick erneut anmieten.

**Architecture:** Der Archiv-Zustand wird nicht gespeichert, sondern aus den Mietvorgängen des Geräts berechnet (neue Methoden auf `Item`). `ItemController::rented` teilt die Liste in aktuell und Archiv; die View zeigt das Archiv als `<details>`-Abschnitt. "Erneut mieten" verlinkt auf das bestehende Formular "Neuer Mietvorgang" mit vorbelegtem Vermieter und Gerät.

**Tech Stack:** Laravel 11, Blade, Alpine.js (nur der vorhandene Geräte-Picker), Pest 3, Pint. Tests auf SQLite und MySQL 8.0.

**Spec:** `docs/superpowers/specs/2026-11-24-mietgeraete-archiv-design.md`

## Global Constraints

- Keine Migration, kein gespeichertes Archiv-Flag; "Geräte", Produktionsplanung, VB-Vorschlag und Verfügbarkeitsregeln bleiben unverändert.
- "Vergangen" = `Mietvorgang::isComplete()` (Rückgabe bestätigt); abgelaufen und nicht zurückgegeben bleibt aktuell mit Markierung "Rückgabe offen".
- Archiv = Gerät mit mindestens einem Mietvorgang und **allen** Mietvorgängen abgeschlossen; Gerät ohne Mietvorgang ist nie im Archiv.
- Archiv als zugeklapptes `<details>` "Archiv (n)", nur wenn n > 0; Suche und Gruppenfilter der Liste gelten auch fürs Archiv.
- "Erneut mieten" nur für `isUser()`; Viewer sehen Archiv und "Details", aber keinen "Erneut mieten"-Button.
- Texte deutsch, Designsystem-Klassen (`card`, `btn`, `badge-*`, `data-table`); jede Eingabe `form-control`/`form-check` (`UiFormsAndDetailsTest`); mobil kein horizontaler Überlauf.
- Pint sauber; Commit-Nachrichten enden mit den Attribution-Zeilen der Session.

## Review Focus

- Gerät mit altem abgeschlossenem **und** kommendem/offenem Mietvorgang bleibt aktuell und zeigt den kommenden/offenen Zeitraum.
- Gerät ohne Mietvorgang: aktuell, "noch nicht gemietet", nie Archiv.
- Abgelaufen, nicht zurückgegeben: aktuell mit "Rückgabe offen".
- Kein Archiv ⇒ keine Archiv-Sektion; Archiv-Zähler und Suche/Gruppenfilter stimmen (auch in den Gruppentabellen `unit_id` 1/2/9/10).
- "Erneut mieten" mit ungültigen Query-Werten (unbekannter Vermieter, Gerät eines anderen Vermieters, Nicht-Mietgerät, Text statt ID): Formular lädt leer bzw. nur mit dem Gültigen, kein 500.
- Nach dem Anlegen des neuen Mietvorgangs ist das Gerät wieder aktuell und nicht mehr im Archiv.
- Massenaktion auf Archivzeilen (Checkbox) funktioniert.

---

### Task 1: Archiv-Zustand am Gerät

**Files:**
- Modify: `app/Models/Item.php`
- Test: `tests/Feature/ItemRentalStateTest.php`

**Interfaces:**
- Produces (auf `App\Models\Item`, arbeiten auf der Relation `mietvorgaenge`, die geladen sein sollte; Kopfzeile des Tests prüft, dass bei geladener Relation keine zusätzliche Abfrage nötig ist):
  - `isRentArchived(): bool` — `mietvorgaenge` nicht leer und jeder `isComplete()`.
  - `hasOpenReturn(): bool` — es gibt einen nicht abgeschlossenen Mietvorgang mit `rent_end` vor heute (`now()->startOfDay()`).
  - `displayMietvorgang(): ?Mietvorgang` — bei Archiv: der Vorgang mit dem spätesten `rent_end`; sonst unter den nicht abgeschlossenen: der laufende (`rent_start <= heute <= rent_end`, bei mehreren der früheste Start), sonst der nächste kommende (kleinster `rent_start > heute`), sonst der jüngste abgelaufene (größtes `rent_end`); ohne Mietvorgang `null`.

- [ ] **Step 1: Failing tests** (`ItemRentalStateTest.php`; Fixtures: Gruppe, `Supplier`, `Item` mit `suppliers_id`, `Mietvorgang::create([... 'rent_start', 'rent_end'])`, Abschluss durch `transport_end_confirmed_at` und `bereit_zur_rueckgabe_confirmed_at` = `now()` am Mietvorgang, Verknüpfung `$mv->items()->attach($item->id)`; heute fest per `$this->travelTo('2026-12-10')`):
  - `ohne Mietvorgang`: `isRentArchived()` false, `hasOpenReturn()` false, `displayMietvorgang()` null.
  - `alle abgeschlossen ⇒ Archiv`: zwei abgeschlossene Mietvorgänge ⇒ `isRentArchived()` true; `displayMietvorgang()` ist der mit dem späteren `rent_end`.
  - `ein offener Mietvorgang verhindert Archiv`: ein abgeschlossener, einer nicht ⇒ false.
  - `abgelaufen aber nicht zurückgegeben`: `rent_end` vor heute, nicht abgeschlossen ⇒ `isRentArchived()` false, `hasOpenReturn()` true; abgeschlossen ⇒ `hasOpenReturn()` false.
  - `Anzeige wählt laufend vor kommend vor abgelaufen`: je ein offener Vorgang (abgelaufen, laufend, kommend) ⇒ laufend; ohne laufenden ⇒ kommend; nur abgelaufene ⇒ der mit größtem `rent_end`.
  - `Mischfall`: alter abgeschlossener + kommender offener ⇒ nicht Archiv, `displayMietvorgang()` = der kommende.
- [ ] **Step 2:** `./vendor/bin/pest tests/Feature/ItemRentalStateTest.php` ⇒ FAIL (Methoden fehlen).
- [ ] **Step 3:** Methoden in `Item` implementieren (Collection-Operationen auf `$this->mietvorgaenge`, `Carbon`-Vergleiche auf Datumsebene).
- [ ] **Step 4:** Tests ⇒ PASS; `./vendor/bin/pint`.
- [ ] **Step 5:** Commit `Mietgeräte: Archiv-Zustand am Gerät`.

### Task 2: Aufteilung und Archiv-Abschnitt in der Liste

**Files:**
- Modify: `app/Http/Controllers/ItemController.php` (`listing` bzw. `rented`)
- Modify: `resources/views/items/index.blade.php`, `resources/views/items/tables/_mietzeitraum.blade.php`
- Create: `resources/views/items/tables/_archiv.blade.php`
- Test: `tests/Feature/ItemRentedArchiveTest.php` (Seitentest); `UiFormsAndDetailsTest`/`UiConsistencyTest` bleiben grün

**Interfaces:**
- Consumes: `Item::isRentArchived()`, `hasOpenReturn()`, `displayMietvorgang()` (Task 1).
- Produces: `ItemController::rented` übergibt zusätzlich `$archived` (`Collection<Item>`, sortiert nach spätestem `rent_end` absteigend) und entfernt diese Geräte aus `$items`; `items.index` (Geräte) übergibt `$archived = collect()`. Das Teilpartial `items.tables._mietzeitraum` zeigt `displayMietvorgang()` (Zeitraum `d.m.Y`, "+N weitere" für die übrigen) und bei `hasOpenReturn()` das Badge `badge-red` "Rückgabe offen"; ohne Mietvorgang weiter `badge-amber` "noch nicht gemietet". `_archiv.blade.php` rendert je Gerät eine Zeile (Tabelle ab `md`, Karten darunter) mit `data-search`, `data-item-id` und (nur `isUser()`) Checkbox `bulk-select form-check`.

- [ ] **Step 1: Failing tests** (`ItemRentedArchiveTest.php`, Admin; `Unit::forceCreate(['id' => 1, …])` für die Kameragruppe wegen MySQL):
  - `abgeschlossenes Gerät steht im Archiv, nicht in der aktuellen Tabelle`: Seite enthält `Archiv (1)` und den Gerätenamen innerhalb des `<details>`-Blocks, nicht davor; ein aktuelles Gerät steht vor dem Archiv-Block.
  - `Gerät ohne Mietvorgang bleibt aktuell mit "noch nicht gemietet"`.
  - `abgelaufen und nicht zurückgegeben bleibt aktuell mit "Rückgabe offen"` (`travelTo`).
  - `Mischfall zeigt den kommenden Zeitraum in der aktuellen Liste`, nicht im Archiv.
  - `keine Archiv-Sektion ohne archivierte Geräte`: HTML enthält nicht `Archiv (`.
  - `Archiv zeigt letzten Zeitraum und Vermieter`: Text "zuletzt" mit `d.m.Y`-Zeitraum und Vermieter-Name.
  - `Gruppenfilter gilt fürs Archiv`: `?unit_id=<andere Gruppe>` blendet das archivierte Gerät aus; `?unit_id=1` (Kameratabelle) zeigt es im Archiv.
  - `Archivzeilen tragen data-search und bulk-select (nur für Benutzer)`: Admin ja, Viewer nein.
  - `Geräte (items.index) hat kein Archiv und zeigt weiter nur eigene Geräte`.
  - `Viewer sieht das Archiv, aber keinen Button "Erneut mieten"`.
  - `UiFormsAndDetailsTest`: die Mietgeräte-Seite mit Archiv in der Seitenliste (Fixture mit archiviertem Gerät) bleibt grün.
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Controller teilt nach `isRentArchived()`; `_archiv.blade.php` als `<details>`-Abschnitt unter den Tabellen, vor der Bulk-Leiste, nur bei nicht leerem Archiv (Zähler); Spalten: Gruppe, Nr., Bezeichnung, Vermieter, "zuletzt <Zeitraum>", Aktionen (`Details`; Button "Erneut mieten" folgt in Task 3). Archiv-Karten auch mobil. Die Suche (`itemSearch`) greift über `data-search` ohnehin auf alle Zeilen; prüfen, dass zugeklappte Treffer das `<details>` nicht verstecken, ohne dass es sich öffnet: bei aktiver Suche wird das Archiv geöffnet, wenn es Treffer hat (kleine Erweiterung in `itemSearch()`; oder Hinweis, wenn der Test das fordert).
- [ ] **Step 4:** Tests dieser Datei, `ItemListSeparationTest`, `SelectionUiTest`, `UiFormsAndDetailsTest`, `UiConsistencyTest` ⇒ PASS; Pint.
- [ ] **Step 5:** Commit `Mietgeräte: Archiv-Abschnitt`.

### Task 3: Erneut mieten

**Files:**
- Modify: `resources/views/items/tables/_archiv.blade.php` (Button), `app/Http/Controllers/MietvorgangController.php` (`create`), `resources/views/mietvorgaenge/create.blade.php`
- Test: `tests/Feature/MietvorgangRentAgainTest.php`

**Interfaces:**
- Consumes: Route `mietvorgaenge.create` (GET, Rolle admin/user), `$assignableItems` im Formular (`Item::whereNotNull('suppliers_id')`).
- Produces: Link `route('mietvorgaenge.create', ['suppliers_id' => $item->suppliers_id, 'item_id' => [$item->id]])` mit Text "Erneut mieten". `MietvorgangController::create(Request)` liest `suppliers_id` (muss existieren) und `item_id[]` (nur Geräte, die Mietgeräte **dieses** Vermieters sind) und gibt `$preselectedSupplierId` (`?int`) und `$preselectedItems` (Collection der gültigen `Item`) an die View; das Formular wählt den Vermieter vor (`old('suppliers_id', $preselectedSupplierId)`) und füllt den Alpine-Picker (`selected`) mit den Geräten (gleiche `{id, label}`-Form wie `items`); die Live-Bezeichnungsvorschlag-Logik für den Vermieter greift beim Laden.

- [ ] **Step 1: Failing tests** (`MietvorgangRentAgainTest.php`):
  - `Archivzeile zeigt "Erneut mieten" mit vorbelegtem Link` (Admin): Link auf `mietvorgaenge.create` mit `suppliers_id` und `item_id[0]` des Geräts; Viewer: kein Link.
  - `Formular ist mit Vermieter und Gerät vorbelegt`: `GET mietvorgaenge.create?suppliers_id=…&item_id[]=…` ⇒ Vermieter-Option `selected`, Gerät als vorgewähltes Element im Seitenquelltext (Picker-Daten `selected` enthält die Geräte-ID und das Label).
  - `ungültige Werte werden ignoriert`: unbekannter Vermieter, Text statt ID, Gerät eines anderen Vermieters, Nicht-Mietgerät ⇒ Status 200, nichts vorgewählt bzw. nur das Gültige.
  - `neuer Mietvorgang holt das Gerät zurück`: Gerät ist im Archiv; `POST mietvorgaenge.store` mit Vermieter, neuem Zeitraum und `item_id[]` ⇒ danach `GET items.rented`: Gerät steht in der aktuellen Liste, nicht mehr im Archiv (Archiv-Sektion verschwunden, wenn es das einzige war).
  - `Viewer: mietvorgaenge.create liefert 403` (bestehendes Verhalten, abgesichert).
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Button in `_archiv`, Controller und Formular wie in Interfaces.
- [ ] **Step 4:** Tests dieser Datei, `ItemRentedArchiveTest`, Mietvorgangs-Tests, `UiFormsAndDetailsTest` ⇒ PASS; Pint.
- [ ] **Step 5:** Commit `Mietgeräte: Erneut mieten`.

### Task 4: Anleitung und Gesamtprüfung

**Files:** `docs/anleitung.md` (Absatz zu Mietgeräten: Archiv, "Rückgabe offen", "Erneut mieten", Hinweis, dass das Archiv die Rückgabe-Bestätigungen im Mietvorgang voraussetzt); Test: `tests/Feature/AnleitungMieteVerleihTest.php` (Ergänzung).

- [ ] **Step 1: Failing test:** Anleitung enthält "Archiv", "Erneut mieten" und "Rückgabe offen".
- [ ] **Step 2:** ⇒ FAIL; **Step 3:** Anleitung ergänzen; Test ⇒ PASS.
- [ ] **Step 4:** `./vendor/bin/pint --test`; `./vendor/bin/pest` SQLite grün; MySQL (`DB_CONNECTION=mysql DB_HOST=localhost DB_SOCKET=/var/run/mysqld/mysqld.sock DB_DATABASE=laravel_test DB_USERNAME=root DB_PASSWORD=`, `service mysql start` falls nötig) grün.
- [ ] **Step 5:** `npm run build`; Demo (`php artisan serve --port=8765`, `demo.sqlite`, ein Mietgerät mit abgeschlossenem Mietvorgang anlegen): Liste mit zugeklapptem und aufgeklapptem Archiv, "Erneut mieten", bei 1280 und 390 px (Überlauf 0). Commit `Anleitung: Mietgeräte-Archiv`; Push auf `claude/optimistic-dirac-0n1u74`; PR erst nach Freigabe.

## Self-Review

- Spec-Abdeckung: Ziel 1–3, 5 ⇒ Tasks 1–2; Ziel 4 ⇒ Task 3; Risiko-Hinweis (Bestätigungen nötig) ⇒ Task 4 (Anleitung) und in der Übergabe an den Anwender.
- Namen konsistent: `isRentArchived()`, `hasOpenReturn()`, `displayMietvorgang()`, `$archived`, Link-Parameter `suppliers_id` / `item_id[]`.
- Proportion: Tests als Aussagen, Implementierung nur als Signaturen und Regeln.
