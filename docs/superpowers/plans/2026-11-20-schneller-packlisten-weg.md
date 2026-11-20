# Schnellerer Weg zur Packliste (Stufe C) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eine Packliste ist in der App in höchstens drei Schritten angelegt und gefüllt, wiederkehrende Produktionen mit einem Klick dupliziert.

**Architecture:** Nur Controller- und Blade-Änderungen an `ProductionController`, `ProductionRequest` und den Produktions-Views; keine Migrationen, keine Änderung an Verfügbarkeits-, Miet- oder Packlogik. Neue Tests als Pest-Feature-Tests.

**Tech Stack:** Laravel 11, Blade, Alpine.js, Tailwind (Klassen aus `resources/css/app.css`), Pest 3, Pint. Tests laufen auf SQLite und MySQL 8.0.

**Spec:** `docs/superpowers/specs/2026-11-20-schneller-packlisten-weg-design.md`

## Global Constraints

- Keine Migrationen, kein Datenmodell-Umbau.
- Datumsformat im Formular bleibt `d.m.Y`; Speicherung `Y-m-d`.
- Rollen: `admin` und `user` (`isUser()`) dürfen anlegen/zuordnen/duplizieren, `viewer` nur lesen.
- UI-Texte deutsch, nur vorhandene Klassen des Designsystems (`btn`, `card`, `card-pad`, `form-control`, `link` …).
- `UiFormsAndDetailsTest` und `UiConsistencyTest` müssen grün bleiben (jedes Formularfeld mit `form-control`, keine Altlasten-Klassen).
- Pint sauber (`vendor/bin/pint --test`), Commit-Nachrichten enden mit den Attribution-Zeilen der Session.

## Review Focus

- Beginn fehlt, Ende leer: weiter Validierungsfehler für den Beginn (Ende leer allein ist erlaubt).
- Ende vor Beginn: weiter Validierungsfehler.
- `?from=<unbekannte id>` auf dem Formular: Seite lädt ohne Vorlage, kein 500.
- `from_production_id` verweist auf gelöschte Produktion: Anlegen klappt, Weiterleitung zur neuen Produktion statt Fehler.
- Viewer sieht weder "Duplizieren" noch Schnelleingabe.
- Produktion mit vorhandenem Material zeigt den Leer-Zustand nicht.

---

### Task 1: Direkt zur Produktion, Ende optional

**Files:**
- Modify: `app/Http/Requests/ProductionRequest.php` (rules)
- Modify: `app/Http/Controllers/ProductionController.php` (`store` ab Zeile 76, `update` ab Zeile 157)
- Modify: `resources/views/productions/create.blade.php`, `resources/views/productions/edit.blade.php` (Label/Hinweis Ende)
- Modify: `resources/views/partials/_quick-add-box.blade.php` (Autofokus)
- Test: `tests/Feature/ProductionCreateFlowTest.php`

**Interfaces:**
- Consumes: bestehende Route `productions.store` (POST), `productions.show`, `productions.importFrom`.
- Produces: `store` leitet auf `route('productions.show', $production)` mit `->with('focus_quick_add', true)`; leeres `booking_end` ⇒ gleich `booking_start`. Session-Key `focus_quick_add` wird von `_quick-add-box` gelesen.

- [ ] **Step 1: Failing tests** in `tests/Feature/ProductionCreateFlowTest.php`: Admin postet `bezeichnung`, `booking_start=10.12.2026`, kein `booking_end`.
  - `redirects to the new production`: `assertRedirect(route('productions.show', $p))`, `$p->booking_end` ist `2026-12-10`.
  - `end before start still fails`: Ende `09.12.2026` ⇒ `assertSessionHasErrors('booking_end')`.
  - `start is still required`: ohne Beginn ⇒ `assertSessionHasErrors('booking_start')`.
  - `import template that no longer exists redirects to the production`: `from_production_id=99999` ⇒ Redirect auf `productions.show`.
  - `quick add is focused after creation`: Folge-GET mit `->withSession(['focus_quick_add' => true])` enthält `autofocus` im Schnelleingabe-Textarea; GET ohne Session-Key nicht.
  - `update with empty end sets end to start`.
- [ ] **Step 2:** `./vendor/bin/pest tests/Feature/ProductionCreateFlowTest.php` ⇒ FAIL.
- [ ] **Step 3:** `ProductionRequest::rules`: `booking_end` wird `['nullable','date_format:d.m.Y','after_or_equal:booking_start']`. In `store` und `update`: `$request->booking_end ?: $request->booking_start` vor dem Parsen. `store` leitet ohne und mit nicht auffindbarer Vorlage auf die Produktion; mit gefundener Vorlage unverändert auf `importFrom`. Label Ende: "Produktionsende (leer = eintägig)". Textarea in `_quick-add-box` bekommt `@if(session('focus_quick_add')) autofocus @endif`.
- [ ] **Step 4:** Test-Datei und `pest tests/Feature/ProductionImportTest.php` ⇒ PASS.
- [ ] **Step 5:** Commit `Produktion anlegen: direkt zur Materialzuordnung, Ende optional`.

### Task 2: Duplizieren

**Files:**
- Modify: `resources/views/productions/_table.blade.php` (Desktop- und Mobilzeile neben "Bearbeiten", beide hinter `isUser()`)
- Modify: `resources/views/productions/show.blade.php` (Kopf-Aktionen)
- Test: `tests/Feature/ProductionDuplicateTest.php`

**Interfaces:**
- Consumes: `route('productions.create', ['from' => $production->id])` (existiert; setzt Namen auf "<Name> (Kopie)", Termine leer, `from_production_id` hidden).
- Produces: Link-Text "Duplizieren" (`btn btn-secondary btn-sm` in der Liste, `btn btn-secondary` im Kopf).

- [ ] **Step 1: Failing tests:** Admin sieht auf `productions.index` und `productions.show` jeweils `route('productions.create', ['from' => $p->id])` und "Duplizieren"; Viewer sieht beides nicht; `GET productions.create?from=<id>` zeigt `value="<Name> (Kopie)"` und `name="from_production_id"`; `?from=99999` ⇒ Status 200 ohne `from_production_id`.
- [ ] **Step 2:** Tests ausführen ⇒ FAIL (Links fehlen).
- [ ] **Step 3:** Links einbauen (Text "Duplizieren").
- [ ] **Step 4:** Tests ⇒ PASS.
- [ ] **Step 5:** Commit `Produktionen duplizieren`.

### Task 3: Vorlagenwahl sichtbar im Formular

**Files:**
- Modify: `resources/views/productions/create.blade.php` (Vorlagenblock ohne Preset)
- Test: `tests/Feature/ProductionCreateTemplatePickerTest.php`

**Interfaces:**
- Consumes: `$productions` (25 letzte) aus `ProductionController::create`; `route('productions.searchTemplates')` für ältere.
- Produces: Vorlagenblock mit Label "Aus früherer Produktion starten" (nicht mehr "optional"), darunter serverseitig gerenderte Links `<a href="{{ route('productions.create', ['from' => $p->id]) }}">Name (Datum)</a>` in einer scrollbaren Liste (`max-h-48 overflow-y-auto`), plus das bestehende Suchfeld, das die Liste filtert und per Fetch ältere nachlädt.

- [ ] **Step 1: Failing tests:** Bei 3 vorhandenen Produktionen enthält `GET productions.create` für jede einen Link mit `?from=<id>` und deren Bezeichnung; Text "Vorlage (optional)" kommt nicht vor; mit `?from=<id>` erscheint stattdessen der Vorlagen-Hinweis "Vorlage:" ohne Liste.
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Block umbauen, Alpine-Filter über vorhandene Links (`x-show` nach Suchtext), Fetch-Nachladen nur wenn nichts gefunden wird.
- [ ] **Step 4:** Tests plus `UiFormsAndDetailsTest` ⇒ PASS.
- [ ] **Step 5:** Commit `Formular: Vorlagen als anklickbare Liste`.

### Task 4: Produktionsseite neu geordnet, Leer-Zustand

**Files:**
- Modify: `resources/views/productions/show.blade.php` (Reihenfolge: Kopf, Leer-Zustand, Raster mit Schnelleingabe/Packliste, dann Mietvorgänge, Vermietvorgänge, VB-Abgleich)
- Modify: `resources/views/productions/_quick-add.blade.php` (Anker-ID)
- Test: `tests/Feature/ProductionShowLayoutTest.php`

**Interfaces:**
- Consumes: `$similarProduction`, `$production->items`, `$production->cameraConfigs` (alle bereits in der View).
- Produces: `id="schnelleingabe"` am Schnelleingabe-Karten-Container; Leer-Zustand-Karte mit `data-empty-state`, nur wenn Benutzer `isUser()` und die Produktion weder Geräte noch Kamerazüge hat; enthält drei Wege: Anker `#schnelleingabe` ("Text einfügen"), Link `productions.importFrom` (nur wenn `$similarProduction`, "Aus <Name> übernehmen"), Anker `#material-hinzufuegen` ("Einzeln wählen"; ID an der Karte "Material hinzufügen" ergänzen).

- [ ] **Step 1: Failing tests:** (a) Produktion mit Mietvorgang und VB-Abgleich: `strpos($html,'Schnell hinzufügen') < strpos($html,'Mietvorgänge (Transport)')` und `< strpos($html,'Abgleich mit VB-Protokoll')`. (b) Leere Produktion, Admin: `data-empty-state` vorhanden, enthält `#schnelleingabe`. (c) Produktion mit einem Gerät: kein `data-empty-state`. (d) Viewer auf leerer Produktion: kein `data-empty-state`. (e) Leere Produktion mit ähnlicher Produktion: Link `productions.importFrom` im Leer-Zustand.
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Blöcke "Mietvorgänge", "Vermietvorgänge" und "Abgleich" hinter das Raster verschieben (nur Reihenfolge, Inhalte unverändert); Leer-Zustand ergänzen.
- [ ] **Step 4:** Alle Tests dieser Datei, `ProductionShowAssignableItemsTest`, `ProductionAssignmentWarningTest`, `UiFormsAndDetailsTest` ⇒ PASS.
- [ ] **Step 5:** Commit `Produktionsseite: Schnelleingabe zuerst, Leer-Zustand mit drei Wegen`.

### Task 5: Gesamtprüfung und Abschluss

**Files:** keine neuen; ggf. `docs/` Anleitung (Abschnitt Produktion anlegen) anpassen, falls dort der alte Ablauf steht.

- [ ] **Step 1:** `./vendor/bin/pint --test` ⇒ sauber (sonst `pint`).
- [ ] **Step 2:** `./vendor/bin/pest` auf SQLite ⇒ alles grün; dasselbe gegen MySQL 8.0 (`DB_CONNECTION=mysql`, wie CI).
- [ ] **Step 3:** `npm run build`; lokale Demo (`php artisan serve --port=8765`) und Playwright-Screenshots von Formular, Produktionsseite (leer und gefüllt), Liste mit Duplizieren; auf Überlauf bei 390 px prüfen.
- [ ] **Step 4:** Zeitnahme: neue Produktion mit Text-Schnelleingabe in der Demo anlegen und die Schrittzahl (Klicks von "Neue Produktion" bis gefüllter Liste) notieren; Ziel höchstens drei.
- [ ] **Step 5:** Push auf `claude/optimistic-dirac-0n1u74`; PR erst nach Freigabe des Nutzers.

## Self-Review

- Spec: Punkt 1 ⇒ Task 1, Punkt 2 ⇒ Tasks 1 und 3, Punkt 3 ⇒ Task 2, Punkt 4 ⇒ Task 4; Ziel "höchstens drei Schritte" ⇒ Task 5 Step 4. Keine Lücken.
- Namen konsistent: `focus_quick_add`, `data-empty-state`, `#schnelleingabe`, `productions.create ?from`.
- Länge angemessen: Tests als Namen und Assertions, kein Implementierungscode.
