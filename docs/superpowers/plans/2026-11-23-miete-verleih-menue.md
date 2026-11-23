# Menü "Miete & Verleih" und getrennte Mietgeräte-Liste Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mietgeräte erscheinen nur auf einer eigenen Seite, eigene Geräte nur unter "Geräte", und das gesamte Mietmanagement (Miete und Verleih) liegt im neuen Menü "Miete & Verleih".

**Architecture:** Die bestehende Geräteliste (`ItemController::index` plus `items/index.blade.php` und die Tabellen-Partials) bekommt einen zweiten Modus "gemietet" unter einer neuen Route; Geräte filtert auf `suppliers_id IS NULL`, Mietgeräte auf `IS NOT NULL`. Die Navigation (Desktop, Mobil) und die Befehlspalette werden umgebaut; Texte und Anleitung nachgezogen. Keine Datenänderung.

**Tech Stack:** Laravel 11, Blade, Tailwind (Designsystem in `resources/css/app.css`), Pest 3, Pint. Tests auf SQLite und MySQL 8.0.

**Spec:** `docs/superpowers/specs/2026-11-23-miete-verleih-menue-design.md`

## Global Constraints

- Keine Migration, keine Änderung an Verfügbarkeit, Zuordnung, VB-Vorschlag, Mietvorgängen, Verleih.
- Menüname exakt **"Miete & Verleih"** (im HTML `Miete &amp; Verleih`); Gruppen im Menü: **Eingang (wir mieten)**: Mietgeräte, Vermieter, Mietvorgänge; **Ausgang (wir verleihen)**: Mieter, Vermietvorgänge.
- "Verwaltung" behält: Gruppen, Gerätetypen, Mailinglisten, Protokoll (Rolle user/admin), Benutzer, Einstellungen (admin).
- Geräteauswahl in Produktionen, VB-Vorschlag, Suche, Timeline, Gruppenseiten, Dashboard-Zahlen bleiben unverändert (zeigen beides).
- Rollen: wer heute die Geräteliste und die Mietseiten sieht (alle Rollen), sieht auch die neuen Menüpunkte und die Mietgeräte-Liste; Anlege-Buttons weiter nur `isUser()`.
- `UiFormsAndDetailsTest`/`UiConsistencyTest` bleiben grün (neue Seite in deren Seitenliste aufnehmen); mobil kein horizontaler Überlauf.
- Pint sauber; Commit-Nachrichten enden mit den Attribution-Zeilen der Session.

## Review Focus

- Gerät ohne Gruppe (`units_id` null) erscheint in der richtigen Liste (Eigenes ohne Vermieter, Mietgerät mit Vermieter).
- Vermieter setzen oder entfernen verschiebt das Gerät zwischen den Listen.
- Sortier-, Filter- und Zurücksetzen-Links auf der Mietgeräte-Seite bleiben auf der Mietgeräte-Seite (springen nicht zu "Geräte").
- Gruppenfilter `unit_id=1/2/9` (Kamera-, Objektiv-, Monitor-Tabellen) funktioniert in beiden Listen.
- Viewer sehen beide Listen, aber keine Anlege-Buttons.
- Alter Link `?herkunft=gemietet` ist harmlos (wird ignoriert, Seite lädt).
- Aktiver Menüpunkt stimmt auf Mietgeräte-, Vermieter-, Mietvorgänge-, Mieter- und Vermietvorgänge-Seiten (inkl. Detail/Bearbeiten), und "Geräte" ist auf der Mietgeräte-Seite **nicht** aktiv.

---

### Task 1: Mietgeräte-Liste und getrennte Geräteliste

**Files:**
- Modify: `app/Http/Controllers/ItemController.php` (`index`, neue Methode `rented`, gemeinsamer privater Aufbau)
- Modify: `routes/web.php` (neue Route in der Gruppe, in der `items.index` definiert ist, **vor** `Route::resource('/items', …)->only(['index','show'])`, damit `items/mietgeraete` nicht als `{item}` gilt)
- Modify: `resources/views/items/index.blade.php`, `resources/views/items/tables/_filter.blade.php`, `_overview.blade.php`, `_cameras.blade.php`, `_lenses.blade.php`, `_monitors.blade.php` (Ziel-Route der Links, Titel)
- Delete: `tests/Feature/ItemHerkunftFilterTest.php`
- Test: `tests/Feature/ItemListSeparationTest.php`

**Interfaces:**
- Produces: Route `GET /items/mietgeraete` → `items.rented` (`ItemController::rented(Request): View`); an die View gehen zusätzlich `$listRoute` (`'items.index'` oder `'items.rented'`) und `$pageTitle` (`'Geräte'` oder `'Mietgeräte'`). Alle `route('items.index', …)` in den fünf Listen-Partials werden zu `route($listRoute, …)`; Fallback `'items.index'`, falls die Variable fehlt (andere Aufrufer).
- Der Filter `herkunft` entfällt komplett (Select in `_filter.blade.php`, Abfrage im Controller, Zurücksetzen-Bedingung).

- [ ] **Step 1: Failing tests** (`ItemListSeparationTest.php`, Fixtures: Gruppe, Lieferant `Supplier::create(['bezeichnung'])`, Items mit/ohne `suppliers_id`, auch eines ohne `units_id`):
  - `Geräte zeigt nur eigene Geräte`: `GET items.index` enthält "Eigene-Kamera", nicht "Miet-Kamera".
  - `Mietgeräte zeigt nur gemietete Geräte`: `GET items.rented` enthält "Miet-Kamera" und den Vermieter-Namen, nicht "Eigene-Kamera"; Seitentitel "Mietgeräte".
  - `Gerät ohne Gruppe erscheint in der passenden Liste`: eigenes ohne Gruppe nur in Geräte, gemietetes ohne Gruppe nur in Mietgeräte.
  - `Vermieter setzen verschiebt das Gerät`: PUT `items.update` mit `suppliers_id` (und den von `ItemRequest` verlangten Mietfeldern; Request-Regeln lesen) ⇒ Gerät nur noch in Mietgeräte; Vermieter leeren ⇒ zurück in Geräte.
  - `Mietgeräte ohne Mietvorgang zeigt "noch nicht gemietet"`; mit Mietvorgang den Zeitraum (`d.m.Y`).
  - `Links bleiben auf der Mietgeräte-Seite`: HTML von `items.rented` enthält `route('items.rented'` -Links (Sortierung/Filterformular `action`) und **keinen** `route('items.index')`-Link im Filterformular/Sortierkopf; mit `?unit_id=<Gruppe>` ebenfalls.
  - `Gruppenfilter funktioniert in beiden Listen` (`unit_id` auf Kameragruppe mit fester ID 1 anlegen: `Unit::forceCreate(['id' => 1, …])` wegen MySQL).
  - `Viewer sieht beide Listen ohne Anlege-Buttons`: Status 200, kein "Neues Gerät".
  - `der Filter Herkunft ist weg`: `items.index` enthält nicht `name="herkunft"`; `?herkunft=gemietet` ändert nichts (eigene Geräte, Status 200).
  - Anpassen: bestehende Tests, die `herkunft` erwarten (nur `ItemHerkunftFilterTest`, wird gelöscht).
- [ ] **Step 2:** `./vendor/bin/pest tests/Feature/ItemListSeparationTest.php` ⇒ FAIL (Route fehlt, gemischte Liste).
- [ ] **Step 3:** Controller: privaten Aufbau `listing(Request $request, bool $rented)` aus dem Rumpf von `index` ziehen (Abfrage `$rented ? whereNotNull('suppliers_id') : whereNull('suppliers_id')`, gleiche Sortierung/Relationen), `index` ⇒ `listing($request, false)`, `rented` ⇒ `listing($request, true)`; View-Daten `listRoute`, `pageTitle`. Route registrieren. Views: `$pageTitle` im Kopf, `$listRoute` in Filterformular, Reset-Link, Sortierlinks; Reset-Bedingung nur noch `request('unit_id')`. In der Mietgeräte-Ansicht müssen "Vermieter" und "Mietzeitraum" sichtbar sein (die Partials zeigen sie teils schon; fehlende ergänzen, "noch nicht gemietet" bei Gerät ohne Mietvorgang). Filter-Select "Herkunft" und `ItemHerkunftFilterTest.php` entfernen.
- [ ] **Step 4:** Tests dieser Datei, `SelectionUiTest`, `UiConsistencyTest`, `UiFormsAndDetailsTest` ⇒ PASS (Mietgeräte-Seite in die Seitenliste von `UiFormsAndDetailsTest` aufnehmen); `./vendor/bin/pint`.
- [ ] **Step 5:** Commit `Mietgeräte als eigene Liste, Geräte nur eigene`.

### Task 2: Menü "Miete & Verleih" und Befehlspalette

**Files:**
- Modify: `resources/views/layouts/navigation.blade.php` (Desktop und Mobil), `resources/views/components/command-palette.blade.php`
- Test: `tests/Feature/NavigationMieteVerleihTest.php`

**Interfaces:**
- Consumes: Route `items.rented` (Task 1), `suppliers.index`, `mietvorgaenge.index`, `mieter.index`, `vermietvorgaenge.index`.
- Produces: Hauptmenü-Dropdown "Miete & Verleih" zwischen "Timeline" und "Verwaltung" mit den zwei Gruppen (Gruppenüberschriften "Eingang (wir mieten)" und "Ausgang (wir verleihen)"); `$mieteRoutes = ['items.rented', 'suppliers.*', 'mietvorgaenge.*', 'mieter.*', 'vermietvorgaenge.*']` bestimmt den aktiven Zustand; `$verwaltungRoutes` verliert `suppliers.*`, `mieter.*`, `mietvorgaenge.*`, `vermietvorgaenge.*`; der Punkt "Geräte" ist **nicht** aktiv auf `items.rented` (aktiv für `items.*` außer `items.rented`). Befehlspalette: neuer Eintrag `['Mietgeräte', route('items.rented'), 'mietgeräte mietpool gemietet']`; die vorhandenen Einträge für Mietvorgänge/Vermietvorgänge bleiben.

- [ ] **Step 1: Failing tests** (`NavigationMieteVerleihTest.php`, Seite `dashboard` als Admin; HTML-Reihenfolge via `strpos`, erst mit `toContain` absichern, damit ein fehlender Text nicht falsch grün ist):
  - `Menü "Miete & Verleih" steht vor "Verwaltung"`: `Miete &amp; Verleih` kommt vor dem ersten `Verwaltung`-Dropdown-Text.
  - `die fünf Ziele liegen im Menü Miete & Verleih`: Links auf `items.rented`, `suppliers.index`, `mietvorgaenge.index`, `mieter.index`, `vermietvorgaenge.index` erscheinen **vor** dem Verwaltung-Trigger und nach dem Miete-&-Verleih-Trigger (Desktop).
  - `Verwaltung enthält die Mietseiten nicht mehr`: zwischen Verwaltung-Trigger und Ende der Desktop-Navigation kommen die vier Links (Vermieter, Mietvorgänge, Mieter, Vermietvorgänge) nicht vor, wohl aber `units.index`, `geraetetypen.index`, `mailing-lists.index`.
  - `Mobilmenü hat dieselbe Struktur` (Gruppenüberschriften "Eingang (wir mieten)", "Ausgang (wir verleihen)" vor der Verwaltung-Gruppe).
  - `aktiver Zustand`: auf `items.rented`, `suppliers.index`, `mietvorgaenge.index`, `mieter.index`, `vermietvorgaenge.index` trägt der Trigger "Miete & Verleih" die aktive Klasse (`border-indigo-400`), "Geräte" nicht auf `items.rented`; auf `units.index` ist Verwaltung aktiv.
  - `Viewer sieht das Menü und die Listen-Links`.
  - `Befehlspalette findet Mietgeräte`: Dashboard-HTML enthält den Eintrag "Mietgeräte" mit URL `items.rented`.
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Navigation umbauen (Dropdown-Komponente wie bei "Verwaltung" wiederverwenden), Mobilmenü gleich, Palette ergänzen.
- [ ] **Step 4:** Tests dieser Datei und die Suite-Teile `Rework/SearchAndAttentionTest`, `BegriffeTest`, `UiConsistencyTest` ⇒ PASS; Pint.
- [ ] **Step 5:** Commit `Menü Miete & Verleih`.

### Task 3: Texte, Anleitung, Aufräumen, Gesamtprüfung

**Files:**
- Modify: `docs/anleitung.md` (Menüübersicht Zeile ~35, Verweise auf "Verwaltung" für Miete/Verleih, Hinweis zu Mietgeräten; die Stelle "Neue Geräte … legt man über **Verwaltung** an", Zeile ~56, korrigieren), weitere Views/Services mit Verweis auf "Verwaltung" für Miet-Seiten (`grep -rn "Verwaltung" resources app docs/anleitung.md`)
- Test: `tests/Feature/AnleitungMieteVerleihTest.php`

**Interfaces:** Consumes: Menüstruktur aus Task 2.

- [ ] **Step 1: Failing test:** `GET` der Anleitung als Admin (Route aus `DocumentationController`) enthält "Miete & Verleih" (maskiert geprüft) und "Mietgeräte", und enthält nicht mehr "Verwaltung** (Dropdown) — Stammdaten (Gruppen, Gerätetypen, Mailinglisten), Miet- und Verleihvorgänge" bzw. jede Formulierung, die Miet-/Verleihvorgänge unter Verwaltung einordnet (konkrete alte Textstelle in der Datei lesen und als Negativ-Assertion verwenden).
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Anleitung und gefundene Verweise anpassen (kurz: Menü "Miete & Verleih" mit Eingang/Ausgang, Mietgeräte-Seite getrennt von Geräte).
- [ ] **Step 4:** `./vendor/bin/pint --test`; `./vendor/bin/pest` auf SQLite grün; MySQL (`DB_CONNECTION=mysql DB_HOST=localhost DB_SOCKET=/var/run/mysqld/mysqld.sock DB_DATABASE=laravel_test DB_USERNAME=root DB_PASSWORD=`, `service mysql start` falls nötig) grün.
- [ ] **Step 5:** `npm run build`; Demo (`php artisan serve --port=8765`, `demo.sqlite`): Menü geöffnet, Geräte und Mietgeräte bei 1280 px und 390 px ansehen (Überlauf 0), Menü aufgeklappt prüfen; Commit `Anleitung und Texte: Miete & Verleih`; Push auf `claude/optimistic-dirac-0n1u74`; PR erst nach Freigabe.

## Self-Review

- Spec-Abdeckung: Ziel 1/2/4 ⇒ Task 1; Ziel 3 (Menü, Palette) ⇒ Task 2; Aufräumen (Anleitung/Texte) ⇒ Task 3; Nicht-Ziele (Timeline, Produktionen, VB-Vorschlag, Suche unverändert) sind durch Globale Regeln abgedeckt und nicht Teil eines Tasks.
- Namen konsistent: Route `items.rented`, Variablen `$listRoute`/`$pageTitle`, Menüname "Miete & Verleih".
- Proportion: Tests als Aussagen, Implementierung nur als Signaturen und Entscheidungen.
