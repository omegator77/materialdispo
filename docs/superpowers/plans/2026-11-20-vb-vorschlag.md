# Packliste aus dem VB-Protokoll vorschlagen Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aus den strukturierten Anforderungen des VB-Protokolls (Kamerazeilen, Typ/Gruppe × Anzahl) einen Vorschlag für Kamerazüge und Material mit freien konkreten Geräten ableiten, prüfen lassen und übernehmen.

**Architecture:** Ein neuer `VbVorschlagService` (`propose()` ohne Seiteneffekte, `apply()` mit Prüfung je Gerät) baut auf `SmartAssignmentService`, `CameraConfigService`, `ProductionAssignmentService`, `ItemAvailabilityService` auf. Ein Controller liefert eine serverseitig gerenderte Vorschau-Seite (GET) und die Übernahme (POST); Einstiegspunkte kommen an die Packlisten-Seite und das VB-Protokoll.

**Tech Stack:** Laravel 11, Blade (kein Alpine nötig), Tailwind-Designsystem (`resources/css/app.css`), Pest 3, Pint. Tests auf SQLite und MySQL 8.0.

**Spec:** `docs/superpowers/specs/2026-11-20-vb-vorschlag-design.md`

## Global Constraints

- Keine Migrationen. Nur strukturierte Anforderungen auswerten, kein Freitext.
- Bestehende Kamerazüge und Zuordnungen werden nie geändert oder gelöscht.
- Ein Gerät wird im selben Vorschlag nie doppelt vergeben; in der Produktion nur an einer Stelle (Regel `ItemAvailabilityService::productionOverlap()`).
- Zug-Namen `Cam {n}` über `CameraSetupService::nextCamNumber()`, bei der Übernahme vergeben; die Vorschau zeigt vorläufige Namen.
- Ein Kamerazug ganz oder gar nicht; die Übernahme insgesamt teilweise, Übersprungenes mit Grund melden.
- Nur Rollen admin/user (Routen in der Gruppe `role:admin,user`); Viewer sehen weder Button noch Seite.
- UI-Texte deutsch, Klassen des Designsystems (`card`, `btn`, `badge-*`, `form-control`); `UiFormsAndDetailsTest` (jedes Feld mit `form-control`, Kopf-Nesting) bleibt grün; mobil kein horizontaler Überlauf.
- Tests mit Kamerazug-Formularen brauchen feste Gruppen-IDs 1 bis 5 (siehe `tests/Feature/CameraConfigDuplicateItemTest.php`), die Vorschau-Seite selbst nicht.
- Pint sauber; Commit-Nachrichten enden mit den Attribution-Zeilen der Session.

## Review Focus

- VB-Protokoll fehlt oder hat nichts Offenes: Seite zeigt "Alles gedeckt", kein Fehler, keine Übernahme-Schaltfläche.
- Kamerazeile ohne Kamera-Typ: kein Zug, Hinweis in der Vorschau.
- Kein freies Gerät des Typs (Kamera): Lücke, kein Zug zum Übernehmen; Zubehör-Slot ohne freies Gerät: Lücke, Zug bleibt übernehmbar.
- Dasselbe Gerät kommt nie zweimal im Vorschlag vor (über Züge und Material hinweg).
- Gerät zwischen Vorschau und Übernahme anderswo vergeben: dieser Teil wird übersprungen mit Grund, der Rest übernommen.
- Zweimal Übernehmen (oder Vorschau danach): nichts doppelt, Vorschlag danach leer.
- Viewer: GET und POST liefern 403.

---

### Task 1: Vorschlag berechnen

**Files:**
- Create: `app/Services/VbVorschlagService.php`
- Test: `tests/Feature/VbVorschlagProposeTest.php`

**Interfaces:**
- Consumes: `SmartAssignmentService::openVbNeeds(Production): array`, `::planFromPool(Collection $pool, array $needs): array` (Zeilen mit `items`, `missing`, `label`, `requested`, `substitutes`), `::ranked(Collection): Collection`; `ItemAvailabilityService::assignableItemsFor(Production, ?string, bool): Collection` (Items mit `is_available`, `availability_reason`); `CameraSetupService::nextCamNumber(Production): int`; `VbProtokollAnforderung` (`cam_number`, `geraetetyp_id`, `lens_geraetetyp_id`, `tripod_geraetetyp_id`, `tripod_head_geraetetyp_id`, `adapter_geraetetyp_id`, Relationen `geraetetyp`, `lensGeraetetyp`, `tripodGeraetetyp`, `tripodHeadGeraetetyp`, `adapterGeraetetyp`).
- Produces: `VbVorschlagService::propose(Production $production): array` mit
  `['rigs' => list<array{line_id:int, cam_label:string, camera_missing:bool, no_type:bool, takeable:bool, slots: array<'camera'|'lens'|'tripod'|'tripod_head'|'large_lens_adapter', array{label:string, typ:?Geraetetyp, item:?Item, alternatives:Collection<Item>, gap:bool, warning:?string}>}>, 'needs' => list<array{label:string, requested:int, missing:int, items:list<Item>, alternatives:Collection<Item>}>, 'empty' => bool]`.
  `takeable` ist wahr, wenn eine Kamera gewählt ist; `no_type` wahr bei Kamerazeile ohne `geraetetyp_id`; `empty` wahr, wenn weder `rigs` noch `needs` Einträge haben; `warning` = "noch nicht gemietet" für Mietgeräte ohne Mietvorgang (Quelle: `check()['warning']`).

- [ ] **Step 1: Failing tests** (`VbVorschlagProposeTest.php`, Helfer baut Gruppe, Geräte, Gerätetypen `Geraetetyp::create(['bezeichnung', 'units_id'])`, Produktion, `VbProtokoll::create(['production_id'])` und `anforderungen()->create([...])`):
  - `ohne VB-Protokoll ist der Vorschlag leer`: `propose()['empty']` ist true.
  - `Kamerazeile ergibt einen Zug mit freiem Gerät je verlangtem Typ`: Zeile `cam_number '1'`, Kamera-/Objektiv-/Stativ-Typ gesetzt, je zwei Geräte pro Typ ⇒ ein Eintrag in `rigs`, `takeable`, jeder Slot hat `item` vom richtigen Typ, `cam_label` "Cam 1".
  - `Slot ohne Typ im VB bleibt leer ohne Lücke`: Kopf-Typ nicht gesetzt ⇒ Slot `tripod_head` hat `item null` und `gap false`.
  - `Typ verlangt aber nichts frei ist eine Lücke, der Zug bleibt übernehmbar`: Stativ-Typ ohne freies Gerät ⇒ `gap true`, `takeable true`.
  - `Keine freie Kamera ergibt keinen übernehmbaren Zug`: `camera_missing true`, `takeable false`.
  - `Kamerazeile ohne Kamera-Typ`: `no_type true`, `takeable false`.
  - `bestehender Zug deckt die erste Zeile desselben Kamera-Typs`: zwei Zeilen gleichen Typs, ein vorhandener `CameraConfig` mit Kamera dieses Typs ⇒ nur ein Eintrag in `rigs`, und zwar für die zweite Zeile; `cam_label` "Cam 2".
  - `Gerät wird nie doppelt vorgeschlagen`: zwei Zeilen gleichen Typs und genau zwei Kameras ⇒ zwei verschiedene Kamera-IDs; ein Gerät, das als Zubehör eines Zugs gewählt ist, taucht nicht in `needs` auf.
  - `übriger Bedarf wird aus Typ und Anzahl gefüllt`: Zeile ohne `cam_number`, Typ × 2 ⇒ `needs[0]['items']` hat 2 Geräte, `missing 0`; mit nur 1 freiem Gerät `missing 1`.
  - `bereits gedeckter Bedarf fehlt im Vorschlag`: Gerät dieses Typs schon in der Packliste, Anzahl 1 ⇒ `needs` leer.
  - `Eigentum kommt vor Miete, Mietgerät ohne Mietvorgang trägt Warnung`: zwei Kameras, eine mit `suppliers_id` ⇒ gewählt ist die eigene; nur Mietgerät frei ⇒ `warning "noch nicht gemietet"`.
  - `propose() ändert nichts`: Zahl der `camera_configs` und `item_production` vorher/nachher gleich.
- [ ] **Step 2:** `./vendor/bin/pest tests/Feature/VbVorschlagProposeTest.php` ⇒ FAIL (Klasse fehlt).
- [ ] **Step 3:** `VbVorschlagService::propose()` implementieren. Algorithmus (nicht durch Signatur bestimmt): Pool = `assignableItemsFor($production, null, true)` mit `is_available` und ohne bereits vergebene IDs; offene Kamerazeilen = VB-Zeilen mit `cam_number !== null`, je `geraetetyp_id` die ersten *n* Zeilen (n = vorhandene Züge der Produktion mit Kamera dieses Typs) überspringen; je offener Zeile pro Slot `ranked()` der freien Geräte des Typs, gewähltes Gerät in die gemeinsame Liste vergebener IDs; vorläufige Namen ab `nextCamNumber()`; danach Bedarf = `openVbNeeds()` mit `planFromPool()` über den um vergebene Geräte verringerten Pool. Alternativen = freie Geräte desselben Typs (ohne das gewählte).
- [ ] **Step 4:** Tests ⇒ PASS; `./vendor/bin/pint`.
- [ ] **Step 5:** Commit `VB-Vorschlag: Berechnung`.

### Task 2: Übernehmen

**Files:**
- Modify: `app/Services/VbVorschlagService.php` (`apply`)
- Test: `tests/Feature/VbVorschlagApplyTest.php`

**Interfaces:**
- Consumes: `propose()` (Task 1); `CameraConfigService::create(Production, array $validated): array{success:bool,error:?string,config:?CameraConfig}` mit Schlüsseln `camera`, `lens`, `tripod`, `tripod_head`, `large_lens_adapter`, `cam_number`; `ProductionAssignmentService::attachMany(Production, iterable $items, ?string $notes = null, bool $notifySlack = true): array{added,skipped,warnings}`; `CameraSetupService::nextCamNumber()`; `SlackVorgangSync::syncProduction()`.
- Produces: `VbVorschlagService::apply(Production $production, array $auswahl): array` mit `$auswahl = ['rigs' => list<array{take:bool, camera:int|string|null, lens:?int, tripod:?int, tripod_head:?int, large_lens_adapter:?int}>, 'items' => list<int>]` und Ergebnis `['created' => list<CameraConfig>, 'attached' => list<Item>, 'skipped' => list<string>, 'warnings' => list<string>]`.

- [ ] **Step 1: Failing tests** (`VbVorschlagApplyTest.php`):
  - `übernimmt gewählte Züge und Material`: zwei Züge (`take` true), 2 Einzelgeräte ⇒ 2 `CameraConfig`, 2 Einträge in `item_production`, `created`/`attached` gefüllt, Namen "Cam 1", "Cam 2".
  - `take false oder fehlende Kamera wird ausgelassen`.
  - `Zug-Namen werden bei der Übernahme vergeben, nicht aus der Vorschau`: zwischen `propose()` und `apply()` entsteht von Hand ein "Cam 1" ⇒ neuer Zug heißt "Cam 2".
  - `Gerät inzwischen vergeben ⇒ Teil wird übersprungen, Rest übernommen`: ein Zug nutzt ein Gerät, das in der Zwischenzeit einer überlappenden anderen Produktion zugeordnet wurde ⇒ `skipped` nennt Gerät und Grund, der andere Zug und das Material sind angelegt, kein halber Zug (`camera_configs` enthält nur vollständige).
  - `dasselbe Gerät in zwei Zügen derselben Übernahme`: zweiter Zug wird mit Grund übersprungen.
  - `zweimal übernehmen verdoppelt nichts`: zweiter Aufruf mit gleicher Auswahl ⇒ nichts Neues, `skipped` nicht leer; `propose()` danach: `empty` true.
  - `Slack wird einmal synchronisiert, wenn etwas übernommen wurde` (`SlackVorgangSync` per `$this->mock` mit `syncProduction` einmal; kein Aufruf, wenn nichts übernommen).
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** `apply()` implementieren: je Zug `CameraConfigService::create()` mit `cam_number = 'Cam '.nextCamNumber()` (Zähler pro erfolgreichem Zug fortschreiben), Fehler ⇒ `skipped[] = "<Zug>: <error>"`; Material gesammelt über `attachMany(..., notifySlack: false)`, `skipped`/`warnings` übernehmen; am Ende einmal `syncProduction`, wenn `created` oder `attached` nicht leer.
- [ ] **Step 4:** Tests ⇒ PASS; Task-1-Tests weiter grün.
- [ ] **Step 5:** Commit `VB-Vorschlag: Übernahme`.

### Task 3: Seite und Routen

**Files:**
- Create: `app/Http/Controllers/VbVorschlagController.php`, `app/Http/Requests/VbVorschlagApplyRequest.php`, `resources/views/vb-vorschlag/show.blade.php`
- Modify: `routes/web.php` (Gruppe `role:admin,user`, neben den `quick-add`-Routen)
- Test: `tests/Feature/VbVorschlagPageTest.php`

**Interfaces:**
- Consumes: `VbVorschlagService::propose()`/`apply()` (Tasks 1–2), `<x-production-steps :production active="packliste">` (Schrittleiste), `<x-page-header>`.
- Produces: Routen `GET productions/{production}/vb-vorschlag` → `productions.vbVorschlag`, `POST productions/{production}/vb-vorschlag` → `productions.vbVorschlag.apply`. Formularfelder der POST-Anfrage: `rigs[<i>][take]` (Checkbox), `rigs[<i>][camera]`, `rigs[<i>][lens]`, `rigs[<i>][tripod]`, `rigs[<i>][tripod_head]`, `rigs[<i>][large_lens_adapter]` (jeweils `<select>` mit freien Geräten und "— leer —"), `items[]` (Selects je Material-Position mit "— weglassen —"). Weiterleitung nach POST auf `productions.show` mit `success` und ggf. `warning` (Übersprungenes mit Grund).

- [ ] **Step 1: Failing tests** (`VbVorschlagPageTest.php`):
  - `Admin sieht Vorschau mit Kamerazug und Material`: Status 200, Text "Cam 1", Gerätebezeichnungen, Button "Übernehmen"; Titel "Packliste aus VB-Protokoll".
  - `ohne Offenes zeigt Alles gedeckt ohne Übernehmen-Button`.
  - `Lücke ist sichtbar`: Text "keins frei" bei fehlendem Stativ; Zugzeile ohne Kamera-Typ zeigt Hinweis "ohne Typ".
  - `POST übernimmt und leitet zur Packliste`: Antwort `assertRedirect(route('productions.show', $p))`, Session `success`, `camera_configs` angelegt.
  - `POST mit Konflikt meldet Übersprungenes`: Session `warning` enthält Gerätenamen.
  - `Viewer: GET und POST liefern 403`.
  - `Validierung`: POST mit nicht existierender Geräte-ID ⇒ Session-Fehler, nichts angelegt.
  - `Produktionsnamen mit Sonderzeichen sind maskiert` (`Müller & <b>Co</b>`).
  - `UiFormsAndDetailsTest`: Seite in die dortige Seitenliste aufnehmen (Raster/Header/`form-control`).
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Controller `show(Production)` (rendert `propose()`), `store(VbVorschlagApplyRequest, Production)` (validiert: `rigs.*.take` boolean, Geräte-IDs `nullable|exists:items,id`, `items.*` `nullable|exists:items,id`; leere Werte filtern; ruft `apply()`; Meldung bauen). View: Schrittleiste, je Zug eine Karte mit Auswahlfeldern je Slot (vorbelegt mit dem gewählten Gerät, Alternativen als Optionen, Lücken als `badge-amber` "keins frei", Mietwarnung als Badge), Liste für übriges Material, Haken "übernehmen" je Zug, Button "Übernehmen" und Link "Abbrechen".
- [ ] **Step 4:** Tests dieser Datei, `UiFormsAndDetailsTest`, `UiConsistencyTest` ⇒ PASS; Pint.
- [ ] **Step 5:** Commit `VB-Vorschlag: Vorschau und Übernahme`.

### Task 4: Einstiegspunkte

**Files:**
- Modify: `resources/views/productions/_quick-add.blade.php` (Button "Offenen Bedarf automatisch zuordnen" ersetzen), `resources/views/productions/show.blade.php` (Leer-Zustand: vierter Weg), `resources/views/vb-protokoll/show.blade.php` (Button)
- Test: `tests/Feature/VbVorschlagEntryPointsTest.php`; bestehende Tests, die den alten Button erwarten, anpassen (`grep -rn "automatisch zuordnen" tests`)

**Interfaces:**
- Consumes: Route `productions.vbVorschlag` (Task 3); `Production::vbProtokoll`.
- Produces: Button-Text "Packliste aus VB-Protokoll vorschlagen" nur wenn ein VB-Protokoll mit mindestens einer Anforderung existiert und der Benutzer `isUser()` ist. Die Route `productions.autoFill` bleibt bestehen, wird aber nicht mehr verlinkt.

- [ ] **Step 1: Failing tests:** Admin mit VB-Protokoll (1 Anforderung) sieht den Button auf `productions.show` (Schnelleingabe-Karte) und auf `vb-protokoll.show`, jeweils mit Link `productions.vbVorschlag`; auf der Packlisten-Seite einer leeren Produktion mit VB-Protokoll auch im Leer-Zustand (`data-empty-state`); ohne VB-Protokoll kein Button; Viewer sieht ihn nirgends; der alte Text "Offenen Bedarf automatisch zuordnen" kommt nicht mehr vor.
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Buttons einbauen; alten Button und seine Bedingung (`$openNeeds`) in `_quick-add` entfernen, soweit nur dafür genutzt (Controller-Variable bleibt, wenn sie sonst gebraucht wird; sonst entfernen und `ProductionShowLayoutTest`/Import-Tests prüfen).
- [ ] **Step 4:** Gesamte Suite ⇒ PASS.
- [ ] **Step 5:** Commit `VB-Vorschlag: Einstiegspunkte`.

### Task 5: Gesamtprüfung

- [ ] **Step 1:** `./vendor/bin/pint --test` ⇒ sauber.
- [ ] **Step 2:** `./vendor/bin/pest` auf SQLite ⇒ grün; MySQL (`DB_CONNECTION=mysql DB_HOST=localhost DB_SOCKET=/var/run/mysqld/mysqld.sock DB_DATABASE=laravel_test DB_USERNAME=root DB_PASSWORD=`, `service mysql start`, falls nötig) ⇒ grün.
- [ ] **Step 3:** `npm run build`; Demo (`php artisan serve --port=8765`, `demo.sqlite`) mit einem VB-Protokoll mit Kamerazeilen und Material füllen; Vorschau und Übernahme per Playwright bei 1280 px und 390 px ansehen, Überlauf 0.
- [ ] **Step 4:** Push auf `claude/optimistic-dirac-0n1u74`; PR erst nach Freigabe.

## Self-Review

- Spec-Abdeckung: Ziele 1–4 ⇒ Tasks 1–3 (Vorschau, Tausch/Weglassen, Übernahme, wiederholbar); Einstiegspunkte ⇒ Task 4; Regeln (Auswahl, Matching, Kamerazug ganz/teilweise Übernahme, Namen bei Übernahme) ⇒ Tasks 1–2; Annahmen aus dem Spec (Zeile = Zug, leere Slots, Mietgeräte, Eingabe ohne Neuableitung) ⇒ in Tests von Task 1–3 festgehalten.
- Namen konsistent: `propose()`/`apply()`, Routen `productions.vbVorschlag` und `.apply`, Slot-Schlüssel `camera|lens|tripod|tripod_head|large_lens_adapter`.
- Proportion: Tests als Namen und Aussagen, nur ein Algorithmus-Absatz.
