# Produktion als durchgehender Ablauf Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Jede Produktions-Seite zeigt dieselbe Schrittleiste (Details · VB-Protokoll · Packliste · Packen) mit Fortschritt, und die Begriffe sind einheitlich.

**Architecture:** Ein Blade-Component `x-production-steps` liest den Stand aus einer neuen Modellmethode `Production::stepStatus()`. Die Leiste wird in die bestehenden Seiten eingebaut, Adressen und Logik bleiben. Danach werden alte Namen ersetzt und die Produktionsliste und die Übersicht "Packen" angepasst.

**Tech Stack:** Laravel 11, Blade, Tailwind (Klassen aus `resources/css/app.css`), Pest 3, Pint. Tests auf SQLite und MySQL 8.0.

**Spec:** `docs/superpowers/specs/2026-11-20-produktions-schritte-design.md`

## Global Constraints

- Keine Migrationen, keine neuen Routen, keine Änderung der Zuordnungs-, Pack- und Abschlusslogik.
- Schritt-Namen exakt: **Details · VB-Protokoll · Packliste · Packen**. "Zuordnen", "Materialzuordnung" und "Packvorgang" verschwinden aus sichtbaren Texten von Views, Menü, Befehlspalette, Dashboard und den Erfolgsmeldungen im `PackvorgangController`.
- Bewusst unverändert: Slack-Texte (`SlackVorgangSync`, `SlackInteractionController`), PDF-Dateiname (`PdfFilename::make('Packvorgang', …)`), Klassen-/Routennamen (`packvorgang.*`).
- Rollen: nur `admin`/`user` (`isUser()`) sehen Anlegen-Links; Betrachter sehen vorhandene Stände.
- Mobil (390 px) kein horizontaler Überlauf; Designsystem-Klassen (`btn`, `badge-*`, `card`).
- `UiFormsAndDetailsTest` und `UiConsistencyTest` bleiben grün. Pint sauber. Commit-Nachrichten enden mit den Attribution-Zeilen der Session.

## Review Focus

- Produktion ohne Geräte: Packliste 0, Packen "0/0", kein Teilen durch null, Packen nicht als "fertig" dargestellt.
- Abgeschlossen mit fehlenden Geräten: Packen zeigt den Stand 8/12 und die Abgeschlossen-Markierung zugleich.
- Betrachter: kein Link "VB-Protokoll anlegen", vorhandene Protokolle und Packen bleiben ansehbar.
- Produktionsliste mit vielen Produktionen: keine zusätzlichen Abfragen je Zeile (N+1).
- Sonderzeichen im Produktionsnamen (Umlaute, `<`, `&`) in Titeln und Leiste werden maskiert.

---

### Task 1: Schrittstand und Component

**Files:**
- Modify: `app/Models/Production.php` (neue Methode nach `packlistEntries()`)
- Create: `resources/views/components/production-steps.blade.php`
- Test: `tests/Feature/ProductionStepsTest.php`

**Interfaces:**
- Produces: `Production::stepStatus(): array` mit Schlüsseln `vb` (bool, Protokoll vorhanden), `packliste` (int, `packlistEntries()->count()`), `packed` (int, `packedItemIds()->count()`), `confirmed` (bool, `packvorgang_confirmed_at !== null`).
- Produces: `<x-production-steps :production="$p" active="details|vb|packliste|packen" />`; aktiver Schritt trägt `aria-current="step"`; Links: Details → `productions.edit`, VB-Protokoll → `vb-protokoll.show` (vorhanden) bzw. `vb-protokoll.create` (fehlt, nur `isUser()`, sonst reiner Text "fehlt"), Packliste → `productions.show`, Packen → `packvorgang.show`. Wurzelelement hat `data-production-steps`.

- [ ] **Step 1: Failing tests** in `ProductionStepsTest.php` (Admin und Viewer via `User::factory()->create(['role' => …])`, Rendern mit `$this->actingAs(...)->blade('<x-production-steps :production="$p" active="packliste" />', ['p' => $p])`):
  - `stepStatus` einer leeren Produktion: `['vb' => false, 'packliste' => 0, 'packed' => 0, 'confirmed' => false]`.
  - Produktion mit 3 Geräten, 2 gepackt (`ProductionItemPack`), VB-Protokoll vorhanden: `vb true`, `packliste 3`, `packed 2`.
  - Leiste zeigt die vier Namen in der Reihenfolge Details, VB-Protokoll, Packliste, Packen; "2/3" bei Packen; `aria-current="step"` genau einmal beim aktiven Schritt.
  - Ohne VB-Protokoll: Admin sieht Link `vb-protokoll.create`, Viewer sieht "fehlt" ohne diesen Link.
  - Abgeschlossen (`packvorgang_confirmed_at` gesetzt) mit 2/3: zeigt "2/3" und ein "abgeschlossen"-Kennzeichen.
  - Leere Produktion: "0/0" und kein "abgeschlossen".
  - Name `Müller & <b>Co</b>` erscheint maskiert, nicht als HTML.
- [ ] **Step 2:** `./vendor/bin/pest tests/Feature/ProductionStepsTest.php` ⇒ FAIL (Methode/Component fehlen).
- [ ] **Step 3:** `stepStatus()` implementieren (nutzt nur vorhandene Relationen, keine zusätzliche Abfrage bei vorgeladenem `items`, `cameraConfigs`, `itemPacks`, `vbProtokoll`); Component bauen (`flex flex-wrap gap-2`, Schritte als `btn btn-secondary btn-sm`, aktiver als `btn btn-primary btn-sm`, Stand als `badge-*`).
- [ ] **Step 4:** Datei-Tests ⇒ PASS.
- [ ] **Step 5:** Commit `Schrittleiste: Stand und Component`.

### Task 2: Leiste einbauen, Seitentitel angleichen

**Files:**
- Modify: `resources/views/productions/show.blade.php` (Titel "Packliste", Leiste unter Kopf), `resources/views/packvorgang/show.blade.php` (Titel "Packen: <Name>"), `resources/views/vb-protokoll/show.blade.php`, `create.blade.php`, `edit.blade.php`, `resources/views/productions/edit.blade.php`
- Test: `tests/Feature/ProductionStepsPagesTest.php`

**Interfaces:**
- Consumes: `<x-production-steps :production active>` aus Task 1. Aktiv-Werte: `productions.show` ⇒ `packliste`, `packvorgang.show` ⇒ `packen`, `vb-protokoll.*` ⇒ `vb`, `productions.edit` ⇒ `details`.

- [ ] **Step 1: Failing tests:** Für jede der sechs Seiten (Admin): Antwort 200, enthält `data-production-steps`, genau ein `aria-current="step"` beim passenden Schritt (Text prüfen). `productions.show` enthält `title`-Text "Packliste" und weder "Materialzuordnung" noch "Zuordnen"; `packvorgang.show` enthält "Packen:" und nicht "Packvorgang:" im Seitentitel. Viewer: Packen- und VB-Seite zeigen die Leiste ohne `vb-protokoll.create`-Link.
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Leiste in die sechs Views einbauen (jeweils direkt unter dem Seitenkopf, im `.page`-Raster; auf den schmalen Formularseiten innerhalb von `.page-narrow` über der Karte), Titel ändern.
- [ ] **Step 4:** Tests dieser Datei, `UiFormsAndDetailsTest`, `UiConsistencyTest`, `ProductionShowLayoutTest`, `ProductionCreateFlowTest` ⇒ PASS.
- [ ] **Step 5:** Commit `Schrittleiste auf allen Produktions-Seiten`.

### Task 3: Begriffe vereinheitlichen

**Files:**
- Modify: `resources/views/productions/_table.blade.php` (zwei Stellen "Zuordnen" → "Packliste"), `resources/views/layouts/navigation.blade.php` (Zeilen 25 und 191: "Packliste" → "Packen"), `resources/views/components/command-palette.blade.php` (Eintrag `['Packen', route('itemprods'), 'packen packliste']`), `resources/views/dashboard.blade.php` (Anleitungstexte Zeilen 82–90), `resources/views/itemproductions/_production-card.blade.php` (Button "Packvorgang" → "Packen", Tooltip), `resources/views/itemproductions/index.blade.php` (Titel "Packen"), `resources/views/packvorgang/show.blade.php` (Texte Zeilen 52, 124, 158, 203), `app/Http/Controllers/PackvorgangController.php` (Erfolgs-/Fehlermeldungen), `app/Services/AttentionService.php` (Titel Zeile 124), `app/Http/Controllers/DashboardController.php` (Label Zeile 244)
- Test: `tests/Feature/BegriffeTest.php`; vorhandene Tests mit alten Texten anpassen (`grep -rn "Packvorgang" tests`).

**Interfaces:** keine neuen; ersetzt Texte. Slack-Texte und `PdfFilename` bleiben (Global Constraints).

- [ ] **Step 1: Failing tests:** Admin-Seiten `productions.index`, `productions.show`, `itemprods`, `packvorgang.show`, `dashboard` enthalten nicht "Zuordnen", "Materialzuordnung", "Packvorgang". Menü (`dashboard`-Seite) enthält Link `route('itemprods')` mit Text "Packen" und keinen Menüpunkt "Packliste". Befehlspalette-Daten enthalten den Titel "Packen". `packvorgang.complete` ⇒ Session-Meldung "Packen als abgeschlossen markiert." (genauer Wortlaut: "Packen abgeschlossen." als eigene Entscheidung des Bauenden, im Test fixiert).
- [ ] **Step 2:** ⇒ FAIL; zusätzlich `grep -rn "Packvorgang" tests` zeigt, welche bestehenden Erwartungen mitwandern.
- [ ] **Step 3:** Texte ersetzen; bestehende Test-Erwartungen auf die neuen Texte ziehen (nur Strings, keine Logik).
- [ ] **Step 4:** Gesamte Suite ⇒ PASS.
- [ ] **Step 5:** Commit `Begriffe vereinheitlicht: Packliste und Packen`.

### Task 4: Produktionsliste mit Stand und Schritten

**Files:**
- Modify: `app/Http/Controllers/ProductionController.php` (`index`: Eager-Loading `items`, `cameraConfigs`, `itemPacks`, `vbProtokoll`), `resources/views/productions/_table.blade.php` (Desktop und Mobil)
- Test: `tests/Feature/ProductionListStepsTest.php`

**Interfaces:**
- Consumes: `Production::stepStatus()` (Task 1).
- Produces: pro Zeile Links "VB-Protokoll", "Packliste" (`productions.show`), "Packen" (`packvorgang.show`) und ein Stand-Hinweis "gepackt x/y" bzw. "abgeschlossen".

- [ ] **Step 1: Failing tests:** (a) Zwei Produktionen, eine mit 3 Geräten/2 gepackt: Liste enthält "2/3" und `route('packvorgang.show', …)`. (b) Abgeschlossene Produktion zeigt "abgeschlossen". (c) Leere Produktion zeigt "0/0" ohne Fehler. (d) Viewer sieht den Stand, aber keinen `vb-protokoll.create`-Link. (e) Abfragezahl: mit 2 vs. 10 Produktionen (je 2 Geräte) ist `DB::getQueryLog()` für `GET productions.index` gleich groß (±0), nach dem Muster von `PerformanceAndUiTest`.
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Eager-Loading ergänzen, Stand-Hinweis und Links in beide Ansichten (Zeilen ohne `isUser()`-Schranke nur lesende Links).
- [ ] **Step 4:** Tests dieser Datei und `UiConsistencyTest` ⇒ PASS.
- [ ] **Step 5:** Commit `Produktionsliste: Packstand und Schritte`.

### Task 5: Übersicht "Packen" mit offenen Produktionen zuerst

**Files:**
- Modify: `app/Http/Controllers/ItemproductionController.php` (`index`: `$allProductions` sortieren), `resources/views/itemproductions/_production-card.blade.php` (deutlicher Button "Packen" mit Stand, Reihenfolge der Buttons)
- Test: `tests/Feature/PackenUebersichtTest.php`

**Interfaces:**
- Consumes: `Production::stepStatus()` (Task 1).
- Produces: `$allProductions` sortiert: nicht abgeschlossene (`packvorgang_confirmed_at` null) zuerst, innerhalb nach `booking_start`; abgeschlossene danach.

- [ ] **Step 1: Failing tests:** Drei Produktionen (A offen, B abgeschlossen, C offen, Beginn aufsteigend B < A < C): Reihenfolge der Bezeichnungen im HTML ist A, C, B. Filter `production_id` zeigt nur die gewählte. Karte enthält Button "Packen" (`btn-primary`) mit Link `packvorgang.show` und den Stand "x/y".
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Sortierung im Controller (Collection `sortBy` nach `[confirmed, booking_start]`), Button hervorheben.
- [ ] **Step 4:** Tests dieser Datei sowie `PerformanceAndUiTest`, `UiConsistencyTest` ⇒ PASS.
- [ ] **Step 5:** Commit `Übersicht Packen: offene Produktionen zuerst`.

### Task 6: Gesamtprüfung

- [ ] **Step 1:** `./vendor/bin/pint --test` ⇒ sauber.
- [ ] **Step 2:** `./vendor/bin/pest` auf SQLite ⇒ alles grün; auf MySQL (`DB_CONNECTION=mysql DB_HOST=localhost DB_SOCKET=/var/run/mysqld/mysqld.sock DB_DATABASE=laravel_test DB_USERNAME=root DB_PASSWORD=`) ⇒ alles grün.
- [ ] **Step 3:** `npm run build`; Demo (`php artisan serve --port=8765`, `demo.sqlite`) und Playwright: Produktionsliste, Packlisten-Seite, Packen-Seite, VB-Protokoll, Übersicht Packen bei 1280 px und 390 px; Überlauf muss 0 sein.
- [ ] **Step 4:** `grep -rniE "zuordnen|materialzuordnung|packvorgang" resources/views app --include=*.php` zeigt nur noch erlaubte Reste (Routen-/Klassennamen, Slack, PDF-Dateiname, Kommentare).
- [ ] **Step 5:** Push auf `claude/optimistic-dirac-0n1u74`; PR erst nach Freigabe.

## Self-Review

- Spec: Leiste ⇒ Tasks 1–2; Namen ⇒ Task 3; Produktionsliste ⇒ Task 4; Übersicht ⇒ Task 5; Erfolg ("nur noch Packliste/Packen") ⇒ Task 6 Step 4.
- Namen konsistent: `stepStatus()` (Schlüssel `vb`, `packliste`, `packed`, `confirmed`), `x-production-steps` mit `active`, `data-production-steps`.
- Entscheidung des Plans: Slack-Texte, PDF-Dateiname und interne Namen bleiben (Slack-Tests und Nachrichten-Wortlaut sind nicht Teil dieser Änderung).
- Länge angemessen: Tests als Namen/Assertions, keine Implementierungskörper.
