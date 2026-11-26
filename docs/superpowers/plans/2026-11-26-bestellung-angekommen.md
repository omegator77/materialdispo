# Bestellung "Sendung angekommen" Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Slack-Nachricht und App zeigen, dass das Paket angekommen ist; der Klick auf "Sendung angekommen" öffnet ein Formular mit optionaler Notiz (z. B. Lagerort).

**Architecture:** "Angekommen" ist eine Information neben den vier Stufen (`stufe()` bleibt unverändert): drei neue Spalten an `bestellungen`, zwei atomare Modellmethoden nach dem Muster von `markRechnungVorhanden`, in Slack ein Knopf plus Modal (`bestellung_angekommen`) und eine Zeile in der Nachricht, in der App Aktionen auf der Detailseite.

**Tech Stack:** Laravel 11, Pest 3, Blade, Slack Block Kit (bestehende Services `SlackBestellungSync`, `BestellungModals`, `SlackInteractionController`).

**Spec:** `docs/superpowers/specs/2026-11-26-bestellung-angekommen-design.md`

## Global Constraints

- Eine additive Migration: `bestellungen.angekommen_at` (dateTime, null), `angekommen_by` (FK `users`, `nullOnDelete`), `angekommen_notiz` (string 255, null). Keine neuen Slack-Scopes.
- `Bestellung::stufe()`, Stufenlabels, Erinnerungsbefehl und Filter "Offen" bleiben unverändert.
- Knopf und Modal nur für Rolle admin/user (`$user->isUser()`); sonst die bestehende Ablehnung (`DENIED_TEXT`). Kein Zugriff für Betrachter in der App (Routengruppe `role:admin,user`).
- Notiz: getrimmt, leer wird `null`, maximal 255 Zeichen (Slack `max_length` und Server-Prüfung).
- Ein zweiter Klick überschreibt nie Zeit, Person oder Notiz einer vorhandenen Ankunft (`markAngekommen` gibt dann `false` zurück).
- Slack-Sync immer nach der Antwort (`dispatch(...)->afterResponse()`); Maskierung über `SlackVorgangSync::escape`; Notiz vor dem Maskieren auf höchstens 255 Zeichen begrenzt.
- Deutsche Oberfläche, Design-System-Klassen (`card`, `btn`, `form-control`, `badge-*`), MySQL-sichere Tests (keine Annahmen über Auto-Increment-Werte).
- Protokoll: lesbarer Eintrag mit dem handelnden Benutzer als Verursacher, kein zusätzlicher automatischer `updated`-Eintrag (Muster `activity()->withoutLogs(...)` + `logStage`).

## Review Focus

- Zwei Klicks kurz hintereinander (Slack/Slack oder Slack/App): genau eine Ankunft, die Notiz des ersten bleibt.
- Betrachter oder unbekanntes Slack-Konto: Formular wird abgelehnt, nichts ändert sich.
- Notiz mit Slack-Sonderzeichen (`<!channel>`, `<@U1>`, `&`, `*`) und 255 Zeichen: keine Erwähnung, Nachricht bleibt gültig.
- Bestellung ist bereits "Bezahlt & abgelegt": Ankunft ist trotzdem möglich bzw. Zeile bleibt stehen, Knopf erscheint nicht mehr.
- Ankunft zurücknehmen und erneut eintragen: Zeile verschwindet und kommt mit neuem Zeitpunkt wieder.
- Leere oder nur aus Leerzeichen bestehende Notiz: wird als keine Notiz gespeichert, Zeile ohne Notizteil.

---

### Task 1: Datenmodell und Modellmethoden

**Files:**
- Create: `database/migrations/2026_11_26_100000_add_angekommen_to_bestellungen_table.php`
- Modify: `app/Models/Bestellung.php`
- Test: `tests/Feature/BestellungModelTest.php`

**Interfaces:**
- Produces: Spalten `angekommen_at` (Carbon-Cast `datetime`), `angekommen_by`, `angekommen_notiz`; Relation `angekommenVon(): BelongsTo` (User via `angekommen_by`); `markAngekommen(User $by, ?string $notiz = null): bool` (true nur bei echter Änderung); `revertAngekommen(User $by): bool`; `istAngekommen(): bool`.

- [ ] **Step 1: Failing tests** in `BestellungModelTest.php`: (a) `markAngekommen` setzt Zeit, Person und getrimmte Notiz und gibt `true` zurück; zweiter Aufruf gibt `false` und lässt Zeit, Person, Notiz unverändert; (b) Notiz aus Leerzeichen oder `null` wird `null` gespeichert; (c) `stufe()` ist vor und nach der Ankunft identisch (z. B. Unterwegs bleibt Unterwegs); (d) Protokoll: genau ein Eintrag mit Event `angekommen`, Verursacher = übergebener Benutzer, kein `updated`-Eintrag, außerhalb jeder angemeldeten Sitzung; (e) `revertAngekommen` leert alle drei Spalten, gibt `true`, bei nicht angekommener Bestellung `false`; genau ein Eintrag `angekommen_zurueckgenommen`; (f) danach ist erneutes `markAngekommen` wieder möglich.
- [ ] **Step 2:** Tests ausführen ⇒ FAIL (`php artisan test --filter=BestellungModelTest`).
- [ ] **Step 3: Migration und Modell.** Migration additiv (`down()` entfernt Spalten und Fremdschlüssel). `Bestellung`: `angekommen_at/by/notiz` in `$fillable`, Cast `angekommen_at => datetime`, `angekommen_at`/`angekommen_notiz` in `logOnly`. `markAngekommen`/`revertAngekommen` nach dem Muster von `markRechnungVorhanden`: bedingtes `update` (`whereNull('angekommen_at')` bzw. `whereNotNull`) in `activity()->withoutLogs`, `refresh()`, Protokoll nur bei echter Änderung über `logStage` (Texte: `Sendung angekommen` plus `(Notiz: …)` falls vorhanden; `Ankunft zurückgenommen`). Notiz vor dem Speichern `trim`, leer ⇒ `null`, `mb_substr` auf 255.
- [ ] **Step 4:** Tests ⇒ PASS; `./vendor/bin/pint`.
- [ ] **Step 5: Commit** `Bestellungen: Ankunft im Modell`.

### Task 2: Slack-Knopf, Formular und Nachrichtenzeile

**Files:**
- Modify: `app/Services/BestellungModals.php`, `app/Services/SlackBestellungSync.php`, `app/Http/Controllers/SlackInteractionController.php`
- Test: `tests/Feature/SlackBestellungFlowTest.php`, `tests/Feature/SlackBestellungSyncTest.php`, `tests/Feature/SlackInteractionTest.php` (nur Formular-JSON)

**Interfaces:**
- Consumes: `Bestellung::markAngekommen(User $by, ?string $notiz): bool`, `istAngekommen()`, `angekommenVon`.
- Produces: `BestellungModals::CALLBACK_ANGEKOMMEN = 'bestellung_angekommen'`; `BestellungModals::angekommen(Bestellung): array` (Modal, `private_metadata` = Bestellungs-ID als String, Titel "Sendung angekommen", Absenden "Speichern", Abbrechen "Abbrechen", ein Input-Block `notiz`: Label "Notiz (optional), z. B. Lagerort", `optional => true`, `max_length` 255); Knopf-Action-ID `bestellung:angekommen`.

- [ ] **Step 1: Failing tests.**
  - Sync (`SlackBestellungSyncTest`): Nachricht ohne Ankunft enthält den Knopf `bestellung:angekommen` (Text "Sendung angekommen") und keine 📦-Zeile; mit Ankunft: kein Knopf, Zeile `📦 Angekommen am 26.11.2026 14:05 Uhr von Anna — Lager B` (mit Notiz) bzw. ohne ` — …` (ohne Notiz); bei `abgelegt_at` bleibt die Zeile stehen und der Knopf fehlt; Notiz mit `<!channel>`, `<@U1>`, `&`, `*` erscheint maskiert, Zeile bleibt unter 3000 Zeichen; Reihenfolge der Knöpfe: Sendung eintragen, Sendung angekommen, Rechnung liegt vor/Bezahlt & abgelegt.
  - Formular-JSON: `callback_id`, `private_metadata`, optionaler Notizblock mit `max_length` 255.
  - Flow (`SlackBestellungFlowTest`): Knopfklick `bestellung:angekommen` eines Benutzers öffnet das Formular (`views.open` mit `trigger_id`), Betrachter/unbekanntes Konto bekommt die Ablehnung über `response_url` und es wird kein Formular geöffnet; `view_submission` mit Notiz setzt Ankunft, antwortet leer (200) und löst `chat.update` aus; ohne Notiz ⇒ Notiz `null`; zu lange Notiz (256) ⇒ `response_action: errors` auf Feld `notiz`, nichts gespeichert; Betrachter beim Absenden ⇒ Fehler auf Feld `notiz`, nichts gespeichert; unbekannte `private_metadata` ⇒ Feldfehler, kein 500; zweites Absenden bei bereits angekommener Bestellung ändert nichts, `chat.update` läuft trotzdem (repariert veraltete Nachricht).
- [ ] **Step 2:** Tests ⇒ FAIL.
- [ ] **Step 3: Implementierung.** `BestellungModals::angekommen` nach dem Muster von `sendung` (vorhandenes `input(...)`; Block `optional` setzen). `SlackBestellungSync::sync`: Knopf `bestellung:angekommen` nur bei `! istAngekommen()` und Stufe ≠ abgelegt, zwischen "Sendung eintragen" und dem Rechnungs-/Ablege-Knopf; nach den Detailzeilen (Details-Section) die 📦-Zeile als eigene Zeile in der Details-Section mit `kurz(...)` und `escape` für Name und Notiz. `SlackInteractionController::handleBestellungAction`: Fall `bestellung:angekommen` öffnet `BestellungModals::angekommen($bestellung)` über `openView`. `handleViewSubmission`: `CALLBACK_ANGEKOMMEN` in die erlaubte Liste, erstes Feld `notiz` für die Ablehnung, neue Methode `submitAngekommen(array $view, User $user)`: Bestellung über `private_metadata` suchen (sonst Feldfehler auf `notiz`), Notiz über `fieldValue($view, 'notiz')` lesen, bei mehr als 255 Zeichen Feldfehler "Maximal 255 Zeichen erlaubt.", sonst `markAngekommen($user, $notiz)`, danach `syncAfterResponse($bestellung)`, `response('', 200)`.
- [ ] **Step 4:** Tests ⇒ PASS; `./vendor/bin/pint`.
- [ ] **Step 5: Commit** `Bestellungen: Sendung angekommen in Slack`.

### Task 3: App-Seite

**Files:**
- Modify: `routes/web.php` (in der Gruppe `['auth','role:admin,user']`), `app/Http/Controllers/BestellungController.php`, `app/Http/Requests/BestellungRequest.php`, `resources/views/bestellungen/show.blade.php`, `resources/views/bestellungen/index.blade.php`, `resources/views/bestellungen/edit.blade.php`
- Test: `tests/Feature/BestellungPageTest.php`

**Interfaces:**
- Consumes: `markAngekommen`, `revertAngekommen`, `istAngekommen`, `angekommenVon`; `SlackBestellungSync::sync`.
- Produces: Routen `POST /bestellungen/{bestellung}/angekommen` (`bestellungen.angekommen`) und `POST /bestellungen/{bestellung}/angekommen/zuruecknehmen` (`bestellungen.angekommen.zuruecknehmen`); Anfrageparameter `notiz` (optional, string, höchstens 255).

- [ ] **Step 1: Failing tests** in `BestellungPageTest.php`: (a) Benutzer/Admin: POST `angekommen` mit Notiz setzt die Ankunft, leitet auf `bestellungen.show` mit Erfolgsmeldung, Slack-Nachricht wird aktualisiert (`chat.update` über `Http::fake`); (b) Betrachter bekommt 403 auf beide Routen; (c) zweites POST bei bereits angekommener Bestellung ⇒ Fehlermeldung "Sendung ist bereits als angekommen eingetragen.", nichts geändert, kein `chat.update`; (d) `zuruecknehmen` leert die Ankunft, Erfolgsmeldung, Sync; ohne Ankunft ⇒ Fehlermeldung "Keine Ankunft zum Zurücknehmen."; (e) Notiz länger als 255 ⇒ Validierungsfehler, nichts gespeichert; (f) Detailseite: ohne Ankunft Knopf "Sendung angekommen" mit Notizfeld, mit Ankunft Zeit, Person, Notiz und Knopf "Ankunft zurücknehmen"; Betrachter sieht Anzeige ohne Knöpfe; (g) Liste zeigt für angekommene Bestellungen eine Kennzeichnung "angekommen" (Badge) in der Sendungsspalte, für andere nicht; (h) Bearbeiten: Notizfeld nur sichtbar und speicherbar, wenn angekommen; `update` ändert `angekommen_at` nie (eingeschleuste Felder `angekommen_at`/`angekommen_by` werden ignoriert).
- [ ] **Step 2:** Tests ⇒ FAIL.
- [ ] **Step 3: Implementierung.** Controller-Aktionen `angekommen(Request)` und `angekommenZuruecknehmen()` nach dem Muster von `rechnung`/`zuruecknehmen` (No-Op über `nichtsZuTun`, Sync nach der Antwort über `syncAfterResponse`); Notiz in `angekommen` mit `$request->validate(['notiz' => ['nullable','string','max:255']])`. `BestellungRequest`: `angekommen_notiz` (nullable, string, max 255, getrimmt) nur zulassen, wenn die Bestellung angekommen ist. Views im bestehenden Stil: Detailseite (Ankunft-Karte oder -Zeile, Aktionsformular), Liste (Badge `badge-*` neben der Sendung), Bearbeiten (Notizfeld bedingt).
- [ ] **Step 4:** Tests ⇒ PASS; Pint; `UiFormsAndDetailsTest` und `UiConsistencyTest` bleiben grün.
- [ ] **Step 5: Commit** `Bestellungen: Sendung angekommen in der App`.

### Task 4: Dokumentation und Gesamtprüfung

**Files:** `docs/anleitung.md` (Abschnitt "Bestellungen": Knopf, Notiz, Zeile, Zurücknehmen in der App), `docs/slack-bestellungen-testliste.md` (Punkte: Knopf klicken mit und ohne Notiz, zweiter Klick, Betrachter, Zurücknehmen in der App), `docs/release-runbook.md` (Hinweis: eine neue Migration, Datensicherung vorher, keine neuen Scopes); Test: `tests/Feature/AnleitungMieteVerleihTest.php` (Ergänzung: Anleitung enthält "Sendung angekommen" und "Lagerort").

- [ ] **Step 1: Failing test:** Anleitung enthält "Sendung angekommen" und "Lagerort".
- [ ] **Step 2:** ⇒ FAIL; **Step 3:** Dokumente ergänzen; Test ⇒ PASS.
- [ ] **Step 4:** `./vendor/bin/pint --test`; `./vendor/bin/pest` auf SQLite grün; MySQL (`DB_CONNECTION=mysql DB_HOST=localhost DB_SOCKET=/var/run/mysqld/mysqld.sock DB_DATABASE=laravel_test DB_USERNAME=root DB_PASSWORD=`, `service mysql start` falls nötig) grün.
- [ ] **Step 5:** `npm run build`; Demo (`php artisan serve --port=8765`, Demo-Datenbank, `php artisan migrate` nur dort): Detailseite und Liste mit angekommener und nicht angekommener Bestellung bei 1280 und 390 px, kein horizontaler Überlauf. Commit `Bestellungen: Anleitung Sendung angekommen`; Push; PR erst nach Freigabe. **Hinweis für die Übergabe:** Slack kann hier nicht live getestet werden; die Testliste für den Anwender ist Teil der Übergabe.

## Self-Review

- Spec-Abdeckung: Ziel 1–2 ⇒ Task 1; Ziel 3 ⇒ Task 2; Ziel 4 ⇒ Task 3; Ziel 5 ⇒ Task 4; Nicht-Ziele (keine Stufe, keine Erwähnung, keine Statusabfrage) ⇒ bewusst ohne Task, durch Tests an `stufe()` (Task 1c) gesichert.
- Namen konsistent: `markAngekommen`, `revertAngekommen`, `istAngekommen`, `angekommenVon`, `CALLBACK_ANGEKOMMEN = 'bestellung_angekommen'`, Action-ID `bestellung:angekommen`, Routen `bestellungen.angekommen` und `bestellungen.angekommen.zuruecknehmen`, Feldname `notiz` (Slack und App-Aktion), `angekommen_notiz` (Spalte und Bearbeiten-Formular).
- Proportion: Tests als Aussagen, Implementierung als Signaturen und Regeln.
