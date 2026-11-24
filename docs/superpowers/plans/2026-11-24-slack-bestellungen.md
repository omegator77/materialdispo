# Bestellungen über Slack Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bestellungen per Slash-Befehl `/bestellung` in Slack erfassen, über vier Stufen (Bestellt → Unterwegs → Rechnung liegt vor → Bezahlt & abgelegt) in einer aktualisierten Slack-Nachricht verfolgen und in der App-Seite "Bestellungen" auflisten; die Buchhaltung bekommt eine Erinnerung, wenn eine Rechnung zu lange unbearbeitet liegt.

**Architecture:** Neue Tabelle `bestellungen` plus Modell `Bestellung` (Stufe wird aus Feldern berechnet). Ein Slash-Befehl-Endpunkt und eine erweiterte `SlackInteractionController::handle` (Aufteilung nach `payload.type`) öffnen Formulare (Modals) und setzen Stufen; `SlackBestellungSync` postet bzw. aktualisiert die Nachricht nach der Antwort (kein Queue-Worker). Eine App-Seite, ein Häkchen "Buchhaltung" am Benutzer und ein täglicher Erinnerungsbefehl ergänzen das.

**Tech Stack:** Laravel 11, Blade, Slack Web API über `Http` (Timeout 5 s, Fehler nur loggen), Pest 3, Pint. Tests auf SQLite und MySQL 8.0; Slack-Aufrufe in Tests mit `Http::fake`.

**Spec:** `docs/superpowers/specs/2026-11-24-slack-bestellungen-design.md`

## Global Constraints

- Keine Dateispeicherung und kein Löschen in Slack; die Rechnung bleibt Datei im Thread.
- Stufen und Texte exakt: **Bestellt**, **Unterwegs**, **Rechnung liegt vor**, **Bezahlt & abgelegt**; Slash-Befehl `/bestellung`; Formularfelder **Bestellt bei**, **Bestellt was**, **Bezahlt mit** (Freitext); Versanddienstleister DHL, DPD, UPS, GLS, Hermes, Sonstige.
- Wer darf was: Befehl, Formulare, "Sendung eintragen", "Rechnung liegt vor": jeder Slack-Nutzer, der per Profil-E-Mail einem App-Benutzer mit Rolle admin/user zugeordnet ist. "Bezahlt & abgelegt" (und sein Zurücknehmen): nur Benutzer mit `ist_buchhaltung` (auch Admins ohne Häkchen nicht). Betrachter und Unbekannte: "Keine Berechtigung".
- Längen: `bestellt_was` höchstens 2000 Zeichen, andere Felder höchstens 255; Slack-Texte maskieren (`SlackVorgangSync::escape`), Sendungsnummer trimmen und im Link URL-kodieren.
- Kein Queue-Worker: Speichern und `views.open` innerhalb der 3 s; Posten/Aktualisieren der Nachricht nach der Antwort (`dispatch(fn () => …)->afterResponse()`).
- Slack-Fehler brechen nie das Speichern ab (nur loggen); Bestellung wird zuerst gespeichert.
- Doppelschutz über eindeutige `slack_view_id`; Zuordnung Slack-ID → App-Benutzer 10 Minuten im Cache (nur Treffer).
- Kanal aus Einstellung `slack_order_channel`, Fallback `config('services.slack.order_channel')` (`.env` `SLACK_ORDER_CHANNEL`); ohne Kanal wird gespeichert, aber nicht gepostet.
- Erinnerung: ab 30 Tagen "Rechnung liegt vor", dann alle 7 Tage; Konstanten, nicht einstellbar.
- Zeit nach `APP_TIMEZONE`. Bestehende Bestätigungsknöpfe (`confirm:*`) bleiben unverändert (`SlackInteractionTest` grün).
- UI deutsch, Designsystem-Klassen, jede Eingabe `form-control`/`form-check` (`UiFormsAndDetailsTest`); mobil kein horizontaler Überlauf.
- Zwei Migrationen: Datensicherung vor dem Deploy (Runbook). Pint sauber; Commit-Nachrichten enden mit den Attribution-Zeilen der Session.

## Review Focus

- Slack wiederholt dasselbe `view_submission` (gleiche `view.id`): genau eine Bestellung, Antwort OK.
- Unbekannter Slack-Nutzer oder Betrachter: kein Formular, keine Änderung, flüchtige Meldung.
- Slack-Ausfall beim Posten: Bestellung bleibt gespeichert; "In Slack neu senden" holt die Nachricht nach.
- Slack-Sonderzeichen in Freitext (`<!channel>`, `<@U1>`, `&`, `*`) lösen keine Erwähnung aus und zerstören die Nachricht nicht.
- Leere oder zu lange Felder: Fehler im Formular, nichts gespeichert.
- Sendungsnummer mit Leerzeichen oder Sonderzeichen: Link korrekt kodiert; "Sonstige" ohne Link.
- Admin ohne Häkchen Buchhaltung kann "Bezahlt & abgelegt" weder in Slack noch in der App setzen.
- Erinnerung: nicht öfter als alle 7 Tage, nie nach "Bezahlt & abgelegt", Buchhaltungs-Benutzer ohne Slack-Konto werden übersprungen.

---

### Task 1: Datenmodell, Stufen und Häkchen Buchhaltung

**Files:**
- Create: `database/migrations/2026_11_24_100000_create_bestellungen_table.php`, `database/migrations/2026_11_24_100100_add_ist_buchhaltung_to_users_table.php`, `app/Models/Bestellung.php`
- Modify: `app/Models/User.php` (fillable, Cast `ist_buchhaltung` boolean, Scope `scopeBuchhaltung`), `app/Http/Controllers/UserController.php`, `app/Http/Requests/StoreUserRequest.php`, `app/Http/Requests/UpdateUserRequest.php`, `resources/views/users/create.blade.php`, `resources/views/users/edit.blade.php`
- Test: `tests/Feature/BestellungModelTest.php`, `tests/Feature/UserBuchhaltungTest.php`

**Interfaces:**
- Produces `App\Models\Bestellung` (Tabelle `bestellungen`; Spalten wie im Spec-Datenmodell: `bestellt_bei`, `bestellt_was`, `bezahlt_mit`, `bestellt_am`, `created_by`, `versanddienstleister`, `sendungsnummer`, `rechnung_vorhanden_at/by`, `abgelegt_at/by`, `letzte_erinnerung_at`, `slack_channel`, `slack_message_ts`, `slack_view_id` unique):
  - Konstanten `STUFE_BESTELLT='bestellt'`, `STUFE_UNTERWEGS='unterwegs'`, `STUFE_RECHNUNG='rechnung'`, `STUFE_ABGELEGT='abgelegt'`, `CARRIERS` (Schlüssel `dhl|dpd|ups|gls|hermes|sonstige` ⇒ Anzeigename).
  - `stufe(): string` (höchste erreichte: `abgelegt_at` ⇒ abgelegt; sonst `rechnung_vorhanden_at` ⇒ rechnung; sonst `sendungsnummer` ⇒ unterwegs; sonst bestellt), `stufeLabel(): string` ("Bestellt", "Unterwegs", "Rechnung liegt vor", "Bezahlt & abgelegt"), `trackingUrl(): ?string` (Muster je Dienstleister, Nummer `rawurlencode`; `sonstige`/ohne Dienst/ohne Nummer ⇒ `null`).
  - Relationen `besteller()`, `rechnungVon()`, `abgelegtVon()` (je `User`); Scopes `scopeOffen`, `scopeAbgelegt`; Activity-Log (`useLogName('bestellung')`, loggt Stufen-/Datenfelder, `HasReadableActivityDescription` mit `activityNoun()` "Bestellung" und `activityLabel()` = `bestellt_bei`).
- Produces `User::ist_buchhaltung` (bool, Standard false), `User::scopeBuchhaltung`.

- [ ] **Step 1: Failing tests:** `BestellungModelTest`: `stufe()` für die vier Zustände (inkl. Sendung nach abgelegt ändert die Stufe nicht); `stufeLabel()` Texte exakt; `trackingUrl()`: DHL, DPD, UPS, GLS, Hermes liefern eine URL, die die Nummer kodiert enthält (z. B. `"12 34/ä"` ⇒ `rawurlencode`), `sonstige`, fehlende Nummer und fehlender Dienst ⇒ `null`; `offen`/`abgelegt`-Scopes; `slack_view_id` eindeutig (zweiter Insert mit derselben ID wirft `QueryException`); Löschen des Benutzers setzt `created_by` auf `null`. `UserBuchhaltungTest`: Häkchen im Formular (Admin-Seiten `users.create`/`users.edit` enthalten Checkbox `name="ist_buchhaltung"` mit Klasse `form-check`); Speichern setzt/entfernt das Flag; Standard false; Nicht-Admin erhält 403 auf die Benutzerseiten (bestehendes Verhalten).
- [ ] **Step 2:** `./vendor/bin/pest tests/Feature/BestellungModelTest.php tests/Feature/UserBuchhaltungTest.php` ⇒ FAIL.
- [ ] **Step 3:** Migrationen, Modell, User-Änderungen, Formulare. Tracking-Muster als Konstante im Modell (Platzhalter `{nummer}`): DHL `https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?piececode={nummer}`, DPD `https://tracking.dpd.de/parcelstatus?query={nummer}&locale=de_DE`, UPS `https://www.ups.com/track?tracknum={nummer}`, GLS `https://gls-group.com/DE/de/paketverfolgung?match={nummer}`, Hermes `https://www.myhermes.de/empfangen/sendungsverfolgung/sendungsinformation/#{nummer}` (Muster einmal gegen die Dienst-Seiten prüfen).
- [ ] **Step 4:** Tests ⇒ PASS; Pint; `./vendor/bin/pest tests/Feature/UiFormsAndDetailsTest.php` ⇒ PASS.
- [ ] **Step 5:** Commit `Bestellungen: Datenmodell und Häkchen Buchhaltung`.

### Task 2: Slack-Grundlagen (Signatur, Zuordnung, Nachricht)

**Files:**
- Create: `app/Support/SlackSignature.php`, `app/Services/SlackUserResolver.php`, `app/Services/SlackBestellungSync.php`
- Modify: `app/Http/Controllers/SlackInteractionController.php` (`verifySignature` delegiert an `SlackSignature`), `config/services.php` (`slack.order_channel`), `app/Http/Controllers/SettingController.php` + `resources/views/settings/edit.blade.php` (Einstellung `slack_order_channel` wie die anderen Kanäle, ohne Schalter)
- Test: `tests/Feature/SlackBestellungSyncTest.php`, `tests/Feature/SlackUserResolverTest.php`, `tests/Feature/SettingsOrderChannelTest.php`

**Interfaces:**
- Produces:
  - `SlackSignature::verify(Request $request): void` — gleiche Regeln wie bisher (fehlendes Secret/Header ⇒ 401, Zeitstempel > 300 s ⇒ 400, falsche Signatur ⇒ 401).
  - `SlackUserResolver::resolve(?string $slackUserId): ?User` — `users.info` (Timeout 5 s), Treffer 10 Minuten im Cache (Schlüssel je Slack-ID), kein Treffer/Fehler nicht gecacht, `null` bei fehlendem Token/ID; `slackIdFor(User $user): ?string` — `users.lookupByEmail`, `null` bei Fehler/kein Treffer.
  - `SlackBestellungSync::sync(Bestellung $bestellung): void` — baut die Nachricht aus dem Zustand (Kopf "🛒 BESTELLUNG — <bestellt_bei>", Zeilen Bestellt bei, Was, Bezahlt mit, Datum `d.m.Y H:i`, Besteller, Stufe, bei Sendung Dienst/Nummer mit Link; Hinweis-Kontextzeile "Rechnung als Datei in den Thread dieser Nachricht hochladen"; Knöpfe: Stufe bestellt/unterwegs/rechnung ⇒ `bestellung:sendung` "Sendung eintragen" und, solange nicht Stufe rechnung/abgelegt, `bestellung:rechnung` "Rechnung liegt vor"; Stufe rechnung ⇒ zusätzlich `bestellung:abgelegt` "Bezahlt & abgelegt"; Stufe abgelegt ⇒ keine Knöpfe, "✅ bezahlt & abgelegt von <Name> am <Datum>"; Knopf-`value` = Bestellungs-ID), postet beim ersten Mal (`chat.postMessage`, speichert `slack_channel`/`slack_message_ts`) sonst `chat.update`; ohne Token/Kanal oder bei Fehler: nichts werfen, Warnung loggen. `threadReply(Bestellung $bestellung, string $text): void` (`chat.postMessage` mit `thread_ts`).
  - Kanal: `slack_channel` der Bestellung ?: `Setting::get('slack_order_channel')` ?: `config('services.slack.order_channel')`.

- [ ] **Step 1: Failing tests:** `SlackBestellungSyncTest` (`Http::fake`, `config(['services.slack.bot_token' => 'xoxb-test', 'services.slack.order_channel' => 'C900'])`): erster Sync postet in `C900`, speichert `slack_message_ts`; zweiter Sync ruft `chat.update` mit dieser `ts`; Nachricht enthält Texte der Stufe, bei `unterwegs` einen Link mit kodierter Nummer; Knöpfe je Stufe (action_ids) laut Interface, bei abgelegt keine; Freitext `<!channel> & *fett* <@U1>` ist maskiert (kein unmaskiertes `<!channel>` im gesendeten Text); ohne Kanal/Token kein HTTP-Aufruf; `ConnectionException` wirft nicht; `threadReply` sendet `thread_ts`. `SlackUserResolverTest`: Treffer per E-Mail, zweiter Aufruf ohne weiteren HTTP-Aufruf (Cache), Fehler/kein Treffer nicht gecacht, `slackIdFor` per `users.lookupByEmail`. `SettingsOrderChannelTest`: Admin speichert `slack_order_channel`, Wert erscheint wieder. Bestehende `SlackInteractionTest` bleibt grün.
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Implementieren; `SlackSignature` aus `verifySignature` herausziehen (Verhalten identisch). `slackApi`-Muster von `SlackVorgangSync` nachbauen (Timeout 5 s, `ConnectionException` fangen).
- [ ] **Step 4:** Tests dieser Dateien, `SlackInteractionTest`, `SlackOutageTest`, `SlackEscapingTest` ⇒ PASS; Pint.
- [ ] **Step 5:** Commit `Bestellungen: Slack-Grundlagen`.

### Task 3: Slash-Befehl, Formulare, Knöpfe

**Files:**
- Create: `app/Http/Controllers/SlackCommandController.php`, `app/Services/BestellungModals.php` (Block-Kit-Aufbau der zwei Formulare)
- Modify: `app/Http/Controllers/SlackInteractionController.php` (`handle` nach `payload.type`), `routes/web.php` (`POST /slack/commands` ⇒ `slack.commands`, außerhalb der Auth-Gruppen), `bootstrap/app.php` (CSRF-Ausnahme `slack/commands`)
- Test: `tests/Feature/SlackBestellungFlowTest.php`

**Interfaces:**
- Consumes: `SlackSignature::verify`, `SlackUserResolver::resolve`, `SlackBestellungSync::sync`, `Bestellung`.
- Produces: `POST /slack/commands` (Form-Post von Slack mit `command`, `user_id`, `trigger_id`): prüft Signatur und Berechtigung (Rolle `isUser()`), antwortet bei Ablehnung mit flüchtiger JSON-Antwort `{"response_type":"ephemeral","text":"🚫 Keine Berechtigung: …"}`, sonst öffnet `views.open` das Formular `callback_id = "bestellung_neu"` (Felder `bestellt_bei`, `bestellt_was` mehrzeilig, `bezahlt_mit`, alle Pflicht) und antwortet 200 leer. `handle()` verarbeitet zusätzlich `block_actions` mit den action_ids `bestellung:sendung` (öffnet Formular `bestellung_sendung`: Select `versanddienstleister` aus `Bestellung::CARRIERS`, Text `sendungsnummer`, `private_metadata` = Bestellungs-ID), `bestellung:rechnung` (setzt `rechnung_vorhanden_at/by`, spricht die Buchhaltung per Thread-Antwort an), `bestellung:abgelegt` (nur `ist_buchhaltung`; setzt `abgelegt_at/by`) und `view_submission` (`bestellung_neu`: legt an, `bestellt_am = now()`, `created_by`, `slack_view_id = view.id`; `bestellung_sendung`: setzt Dienst/Nummer); Validierungsfehler als `{"response_action":"errors","errors":{feld: text}}`; nach Erfolg `dispatch(fn () => $sync->sync($bestellung))->afterResponse()`. Alle Aktionen schreiben ins Activity-Log mit dem zugeordneten Benutzer als Verursacher. Andere action_ids (`confirm:*`, Debug-Fall) verhalten sich wie bisher.

- [ ] **Step 1: Failing tests** (`SlackBestellungFlowTest.php`; Signatur-Helfer wie in `SlackInteractionTest` nachbauen, aber unter anderem Namen, da Pest-Hilfsfunktionen global sind; `Http::fake` für `users.info`, `views.open`, `chat.*`):
  - `Befehl ohne gültige Signatur ⇒ 401`; `Befehl von unbekanntem Nutzer oder Betrachter ⇒ flüchtige Ablehnung und kein views.open`.
  - `Befehl öffnet das Formular`: `views.open` wurde mit `trigger_id` und `callback_id` `bestellung_neu` und den drei Feldern gesendet.
  - `Absenden legt die Bestellung an`: Felder, `bestellt_am` ≈ jetzt, `created_by` = zugeordneter Benutzer, `slack_view_id` gesetzt; `chat.postMessage` ging in den Kanal; `slack_message_ts` gespeichert.
  - `Wiederholtes Absenden mit derselben view.id`: genau eine Bestellung, Antwort 200.
  - `Validierung`: leere Pflichtfelder, `bestellt_was` > 2000 Zeichen, andere > 255 ⇒ `response_action: errors` mit Feldnamen, nichts gespeichert.
  - `Sendung eintragen`: Knopf öffnet Formular mit `private_metadata`; Absenden setzt Dienst und Nummer (getrimmt), Stufe `unterwegs`, `chat.update`.
  - `Rechnung liegt vor`: jeder Benutzer/Admin setzt die Stufe; Thread-Antwort erwähnt die Buchhaltungs-Benutzer mit Slack-Konto (`<@U…>`), überspringt solche ohne.
  - `Bezahlt & abgelegt`: Benutzer mit Häkchen erfolgreich; Admin ohne Häkchen und normaler Benutzer abgelehnt (flüchtige Meldung, Zustand unverändert); Nachricht wird ohne Knöpfe aktualisiert.
  - `Slack-Ausfall beim Posten`: `ConnectionException` auf `chat.postMessage` ⇒ Bestellung gespeichert, Antwort 200.
  - `Activity-Log`: Einträge für anlegen, Sendung, Rechnung, abgelegt mit Verursacher.
  - Bestehende Tests in `SlackInteractionTest` bleiben grün (Regression).
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Controller, Modals, `handle()`-Aufteilung (`payload.type`: `block_actions` ⇒ bisheriges Verhalten für `confirm:`/Debug und neu `bestellung:*`; `view_submission` ⇒ neu), Route und CSRF-Ausnahme; Berechtigungsprüfung mit `SlackUserResolver` vor jeder Änderung; Slack-Antwortzeit beachten (keine Posts vor der Antwort).
- [ ] **Step 4:** Tests dieser Datei und alle `Slack*Test` ⇒ PASS; Pint.
- [ ] **Step 5:** Commit `Bestellungen: Slash-Befehl, Formulare und Knöpfe`.

### Task 4: App-Seite "Bestellungen", Menü, Einstellungen

**Files:**
- Create: `app/Http/Controllers/BestellungController.php`, `app/Http/Requests/BestellungRequest.php`, `resources/views/bestellungen/index.blade.php`, `show.blade.php`, `edit.blade.php`
- Modify: `routes/web.php` (Lesen `bestellungen.index/show` für alle angemeldeten Benutzer; Schreiben in der Gruppe `role:admin,user`: `bestellungen.edit/update`, `bestellungen.rechnung`, `bestellungen.abgelegt`, `bestellungen.zuruecknehmen`, `bestellungen.resync`), `resources/views/layouts/navigation.blade.php` (Desktop und Mobil: in "Verwaltung" neue Gruppe "Einkauf" mit "Bestellungen"; `$verwaltungRoutes` um `bestellungen.*` erweitern), `resources/views/components/command-palette.blade.php` (Eintrag "Bestellungen")
- Test: `tests/Feature/BestellungPageTest.php`; Seiten in `UiFormsAndDetailsTest`/`UiConsistencyTest` aufnehmen

**Interfaces:**
- Consumes: `Bestellung` (Task 1), `SlackBestellungSync::sync` (Task 2).
- Produces: Liste `bestellungen.index` mit Filter `status` = `offen` (Standard: alles außer abgelegt) | `abgelegt` | `alle` und Suche `q` (bestellt bei, was, Sendungsnummer, Besteller); Tabelle Datum, Besteller, Bestellt bei, Bestellt was, Bezahlt mit, Sendung (Link `trackingUrl()`), Stufe-Badge; Detailseite mit Stufenverlauf und Knöpfen "Rechnung liegt vor", "Bezahlt & abgelegt" (nur `ist_buchhaltung`), "Stufe zurücknehmen" (nur für die letzte Stufe: abgelegt ⇒ nur Buchhaltung, sonst admin/user), "In Slack neu senden"; Bearbeiten-Formular für alle Felder inkl. Dienstleister und Sendungsnummer. Jede Schreibaktion aktualisiert die Slack-Nachricht (`sync`) und schreibt ins Activity-Log.

- [ ] **Step 1: Failing tests:** Liste zeigt standardmäßig nur offene (nicht abgelegte); `?status=abgelegt` nur abgelegte; `alle` beide; Suche nach Sendungsnummer und Besteller; Viewer sieht Liste und Detail, aber keine Knöpfe und bekommt 403 auf Schreibrouten; Bearbeiten speichert und ruft `chat.update` auf (`Http::fake`); `bestellungen.rechnung` setzt die Stufe; `bestellungen.abgelegt` nur mit `ist_buchhaltung` (Admin ohne Häkchen: 403 oder Ablehnung ohne Änderung); Zurücknehmen von abgelegt nur Buchhaltung, von rechnung für Benutzer/Admin; "In Slack neu senden" postet eine Nachricht, wenn `slack_message_ts` fehlt (Kanal gesetzt), sonst aktualisiert; Validierung (Pflichtfelder, Längen); Menü: Verwaltung enthält Gruppe "Einkauf" mit Link `bestellungen.index` (Desktop und Mobil), `Einkauf`-Seiten markieren Verwaltung als aktiv; Seiten haben `form-control` an jedem Feld.
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Controller, Request, Views im Designsystem (`page`, `card`, `data-table`, `badge-*`), Routen, Menü, Palette.
- [ ] **Step 4:** Tests dieser Datei, `UiFormsAndDetailsTest`, `UiConsistencyTest`, `NavigationMieteVerleihTest` ⇒ PASS; Pint.
- [ ] **Step 5:** Commit `Bestellungen: App-Seite und Menü`.

### Task 5: Erinnerung an die Buchhaltung

**Files:**
- Create: `app/Console/Commands/RemindBestellungenCommand.php`
- Modify: `routes/console.php` (täglich 09:00, `withoutOverlapping()`)
- Test: `tests/Feature/BestellungReminderTest.php`

**Interfaces:**
- Consumes: `Bestellung`, `SlackBestellungSync::threadReply`, `SlackUserResolver::slackIdFor`.
- Produces: `php artisan bestellungen:erinnern`: wählt Bestellungen mit `rechnung_vorhanden_at <= now()->subDays(30)`, ohne `abgelegt_at`, mit `slack_message_ts` und (`letzte_erinnerung_at` null oder `<= now()->subDays(7)`); postet je Bestellung eine Thread-Antwort mit Erwähnung der Buchhaltungs-Benutzer, die ein Slack-Konto haben (`<@U…>`), und setzt danach `letzte_erinnerung_at = now()` (nur bei erfolgreichem Posten).

- [ ] **Step 1: Failing tests** (`travelTo` fest): nach 31 Tagen genau eine Erinnerung; unter 30 Tagen keine; zweiter Lauf am selben Tag keine; nach 8 Tagen wieder eine; nie nach `abgelegt_at`; ohne `slack_message_ts` keine; Buchhaltungs-Benutzer ohne Slack-Konto wird nicht erwähnt, andere schon; ist niemand erwähnbar, trotzdem Erinnerung ohne Namen oder überspringen (im Test pinnen: Erinnerung mit Text "Buchhaltung"); Slack-Fehler ⇒ `letzte_erinnerung_at` bleibt `null`; Befehl ist im Scheduler (`schedule:list` enthält `bestellungen:erinnern`).
- [ ] **Step 2:** ⇒ FAIL.
- [ ] **Step 3:** Befehl und Scheduler-Eintrag.
- [ ] **Step 4:** Tests ⇒ PASS; Pint.
- [ ] **Step 5:** Commit `Bestellungen: Erinnerung`.

### Task 6: Dokumentation und Gesamtprüfung

**Files:** `docs/anleitung.md` (Abschnitt "Bestellungen": `/bestellung`, Stufen, Rechnung im Thread, Buchhaltung), `docs/betrieb.md` (Slack-Einrichtung: Befehl `/bestellung` mit Adresse `/slack/commands`, Scope `commands`, Bot in den Kanal, Einstellung/`SLACK_ORDER_CHANNEL`, Migrationen und Datensicherung, Scheduler-Eintrag), `.env.example` (`SLACK_ORDER_CHANNEL`), `docs/release-runbook.md` (Hinweis auf zwei neue Migrationen); Test: `tests/Feature/AnleitungMieteVerleihTest.php` (Ergänzung: "Bestellungen", "Bezahlt & abgelegt", "/bestellung").

- [ ] **Step 1: Failing test:** Anleitung enthält "Bestellungen", "Bezahlt &amp; abgelegt" und "/bestellung".
- [ ] **Step 2:** ⇒ FAIL; **Step 3:** Dokumente ergänzen; Test ⇒ PASS.
- [ ] **Step 4:** `./vendor/bin/pint --test`; `./vendor/bin/pest` auf SQLite grün; MySQL (`DB_CONNECTION=mysql DB_HOST=localhost DB_SOCKET=/var/run/mysqld/mysqld.sock DB_DATABASE=laravel_test DB_USERNAME=root DB_PASSWORD=`, `service mysql start` falls nötig) grün.
- [ ] **Step 5:** `npm run build`; Demo (`php artisan serve --port=8765`, `demo.sqlite`, `php artisan migrate` auf der Demo-DB): Seite "Bestellungen" mit Demo-Bestellungen in allen Stufen bei 1280 und 390 px (Überlauf 0). Commit `Bestellungen: Anleitung und Betrieb`; Push; PR erst nach Freigabe. **Hinweis für die Übergabe:** Slack selbst kann hier nicht live getestet werden (kein Zugang); eine manuelle Testliste für den Anwender in die Übergabe schreiben (Befehl absetzen, Formular, Sendung, Rechnung, Bezahlt & abgelegt als Buchhaltung, Erinnerung per `php artisan bestellungen:erinnern` mit Testdaten).

## Self-Review

- Spec-Abdeckung: Ziele 1–5 ⇒ Tasks 2–3; Ziel 6 (Erinnerung) ⇒ Task 5; Ziel 7 (App-Seite, Menü) ⇒ Task 4; Ziel 8 (Häkchen) und Datenmodell ⇒ Task 1; Einrichtung und Betrieb ⇒ Task 6; Fehlertoleranz, Doppelschutz, Berechtigung, Maskierung ⇒ Tasks 2–3 mit Tests; Nicht-Ziele (Dateien, Löschen, Statusabfrage) bewusst ohne Task.
- Namen konsistent: `Bestellung::stufe()/stufeLabel()/trackingUrl()`, `SlackBestellungSync::sync()/threadReply()`, `SlackUserResolver::resolve()/slackIdFor()`, action_ids `bestellung:sendung|rechnung|abgelegt`, callback_ids `bestellung_neu|bestellung_sendung`, Routen `bestellungen.*`, `slack.commands`.
- Proportion: Tests als Aussagen, Implementierung als Signaturen und Regeln.
