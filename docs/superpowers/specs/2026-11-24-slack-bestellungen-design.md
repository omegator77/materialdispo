# Bestellungen über Slack erfassen und Pakete nachverfolgen

## Intent / Warum
Bestellungen laufen heute ohne festen Ort. Das Team will sie in Slack mit einem kurzen
Formular erfassen, später die Sendungsverfolgung nachtragen und am Ende die Rechnung ablegen.
Zwei Empfänger: die **Buchhaltung** braucht die Rechnungen; das Team will **Pakete nachverfolgen**
("was ist noch unterwegs?"). Die Rechnung muss **nicht in der App** gespeichert werden: es genügt,
wenn die Buchhaltung sie aus Slack herunterladen kann.

## Entscheidungen des Anwenders
- Ablauf: Auslöser in Slack → Formular mit **Bestellt bei**, **Bestellt was**, **Bezahlt mit**
  (Datum automatisch) → später **Sendungsverfolgung** nachtragen → abschließend **Rechnung**.
- Gewählter Weg: **Slash-Befehl** + Formular (Modal) + eine Slack-Nachricht je Bestellung, die
  aktualisiert wird, + kleine Tabelle und Seite "Bestellungen" in der App.
- Keine Dateispeicherung in der App.

## Ist-Zustand (Befund)
- Slack-Zugang vorhanden: Bot-Token, Signing-Secret, Endpunkt `POST /slack/interactions`
  (CSRF-frei, Signaturprüfung mit Zeitfenster 300 s). Bisher nur Knopfklicks.
- Der klickende Slack-Nutzer wird per Profil-E-Mail (`users.info`) einem App-Benutzer
  zugeordnet; nur Rolle admin/user darf schreiben, sonst Antwort "Keine Berechtigung".
- `SlackVorgangSync` zeigt das Muster: je Vorgang eine Nachricht (`chat.postMessage`, danach
  `chat.update` auf `slack_message_ts`), Slack-Fehler brechen das Speichern nie ab
  (`slackApi()` mit Timeout 5 s). Kanäle kommen aus Einstellungen (`slack_reminder_channel`,
  `slack_production_channel`, Fallback `.env`).

## Ziele
1. **`/bestellung`** (Slash-Befehl) öffnet sofort ein Formular: Bestellt bei, Bestellt was
   (mehrzeilig), Bezahlt mit (Freitext). Pflichtfelder: Bestellt bei, Bestellt was, Bezahlt mit.
2. Absenden legt eine **Bestellung** an (Datum = jetzt, Besteller = zugeordneter App-Benutzer) und
   postet eine Nachricht in den Bestell-Kanal: Titel, Bestellt bei, Was, Bezahlt mit, Datum,
   Besteller, Status; Knöpfe **Sendung eintragen** und **Abschließen**; Hinweis "Rechnung als Datei
   in den Thread dieser Nachricht hochladen".
3. **Sendung eintragen** öffnet ein Formular (Versanddienstleister, Sendungsnummer) und
   aktualisiert dieselbe Nachricht (mit Tracking-Link, sofern der Dienstleister bekannt ist). Ist
   bereits eine Sendung eingetragen, kann sie überschrieben werden.
4. **Abschließen** markiert die Bestellung als abgeschlossen (Zeitpunkt, wer) und kompaktiert die
   Nachricht (Knöpfe weg, "✅ abgeschlossen"). Die Rechnung liegt dann als Datei im Thread.
5. **App-Seite "Bestellungen"**: Liste mit Datum, Besteller, bei, was, bezahlt mit, Sendung
   (Link), Status; Filter "Offen / Abgeschlossen / Alle" (Standard Offen) und Suche; Detail- und
   Bearbeiten-Seite (Tippfehler korrigieren, Sendung nachtragen, abschließen/wieder öffnen); jede
   Änderung aktualisiert die Slack-Nachricht. Im Menü unter "Verwaltung" in neuer Gruppe "Einkauf".

## Nicht-Ziele
- Keine Speicherung oder Erkennung der Rechnung in der App (kein Mithören von Nachrichten oder
  Datei-Uploads; "Abschließen" ist ein bewusster Klick).
- Kein Abruf des Sendungsstatus bei den Paketdiensten, keine Erinnerungen, keine Summen/Exporte.
- Keine Verknüpfung mit Produktionen, Geräten oder Mietvorgängen.
- Kein Stichwort in normalen Nachrichten (nur der Slash-Befehl).

## Anti-Ziele
- Eine Bestellung geht verloren oder wird doppelt angelegt (Slack wiederholt Anfragen, die nicht
  innerhalb von 3 s beantwortet werden).
- Ein Slack-Fehler verhindert das Speichern; oder die App und die Slack-Nachricht widersprechen
  sich dauerhaft.
- Ein unbekannter Slack-Nutzer oder Betrachter legt Bestellungen an oder ändert sie.

## Entscheidungen des Autors (Annahmen, bitte prüfen)
Nicht vom Anwender entschieden; jede mit Kosten bei Irrtum.
- **"Bezahlt mit" ist Freitext** (z. B. "Firmenkarte"). Später ließe es sich in eine Auswahl
  umbauen. Irrtum: uneinheitliche Schreibweisen.
- **Versanddienstleister als feste Auswahl** (DHL, DPD, UPS, GLS, Hermes, Sonstige) für den
  Tracking-Link; bei "Sonstige" nur die Nummer. Irrtum: ein Dienst fehlt; Link-Muster ändert sich.
- **Abschließen ist ein Klick** nach dem Hochladen der Rechnung im Thread (kein automatisches
  Erkennen). Irrtum: Bestellungen bleiben offen, wenn niemand klickt.
- **Wer darf:** Slack-Benutzer, die per E-Mail einem App-Benutzer mit Rolle admin/user zugeordnet
  sind, für Befehl, Formulare und Knöpfe; Betrachter dürfen die Seite nur lesen.
- **Bestell-Kanal** kommt aus einer neuen Einstellung `slack_order_channel` (wie die anderen
  Kanäle, mit `.env`-Fallback `SLACK_ORDER_CHANNEL`); der Bot muss im Kanal sein. Ohne Kanal wird
  die Bestellung trotzdem gespeichert (nur ohne Slack-Nachricht, mit Hinweis in der Antwort).
- **Zeitzone/Datum:** `bestellt_am` ist der Zeitpunkt des Absendens (Zeitzone der App).
- **Fehlertoleranz:** Speichern vor Slack-Aufruf; schlägt Slack fehl, bleibt die Bestellung
  erhalten und die Nachricht lässt sich über "Bearbeiten" in der App erneut synchronisieren.

## Entscheidungen des Autors aus der Prüfung des Entwurfs
Auch diese hat der Anwender nicht entschieden; jede mit Kosten bei Irrtum.
- **Kein Queue-Worker.** Die App hat bewusst keinen (siehe `.env.example`). Innerhalb der 3 Sekunden
  passieren nur Signaturprüfung, Berechtigung (aus dem Cache, siehe unten) und `views.open` bzw. das
  Speichern der Bestellung. Das Posten und Aktualisieren der Slack-Nachricht läuft **nach der
  Antwort** (`dispatch(...)->afterResponse()`, ohne Worker). Irrtum: bei einem Absturz zwischen
  Speichern und Posten fehlt die Slack-Nachricht, sie wird über "In Slack neu senden" nachgeholt.
- **Berechtigung vor `views.open`**, mit zwischengespeicherter Zuordnung Slack-ID → App-Benutzer
  (10 Minuten, nur Treffer; kein Treffer wird nicht gecacht). Schlägt `users.info` fehl oder ist
  unbekannt: ablehnen ("Keine Berechtigung"), nicht öffnen. Irrtum: eine eben geänderte Rolle gilt
  bis zu 10 Minuten verzögert.
- **Doppelschutz:** Bestellung zuerst speichern mit eindeutiger `slack_view_id`; kollidiert sie bei
  einer Slack-Wiederholung, wird nichts neu angelegt und mit OK geantwortet. Eine noch ausstehende
  oder fehlgeschlagene Slack-Nachricht stößt eine Wiederholung **nicht** selbst an, nur "In Slack
  neu senden" in der App.
- **Neu senden:** Detailseite hat "In Slack neu senden"; jedes Speichern auf der Bearbeiten-Seite
  synchronisiert ebenfalls. Eine Bestellung ohne `slack_message_ts` wird dabei erstmals gepostet,
  sobald ein Kanal eingestellt ist.
- **Einstellung `slack_order_channel`** kommt auf die bestehende Einstellungsseite (wie die anderen
  Kanäle, ohne Ein-/Ausschalter).
- **`handle()` wird nach `payload.type` aufgeteilt** (`block_actions`, `view_submission`);
  abgelehnte oder ungültige `view_submission` antworten mit `response_action: errors` im Formular,
  Knopfklicks mit einer flüchtigen Antwort nur für den Klickenden.
- **Wer darf Sendung eintragen/abschließen:** jeder Admin/Benutzer, nicht nur der Besteller.
  "Wieder öffnen" gibt es nur in der App; die Slack-Nachricht bekommt dabei ihre Knöpfe zurück.
- **Zeitzone:** Datum und Uhrzeit nach `APP_TIMEZONE` (in `docs/betrieb.md` für Produktion als
  `Europe/Berlin` vorgesehen); die App formatiert in dieser Zone.
- **Längen:** "Bestellt was" höchstens 2000 Zeichen, die anderen Felder höchstens 255; Slack-Texte
  werden maskiert (`SlackVorgangSync::escape`), die Sendungsnummer wird getrimmt und im Link
  URL-kodiert.
- **Protokoll:** alle Aktionen (anlegen, Sendung, abschließen, wieder öffnen) landen im Activity-Log
  mit dem zugeordneten Benutzer als Verursacher.
- **Slack-Einrichtung:** vorhandene Scopes (`chat:write`, `users:read`, `users:read.email`) genügen
  für Nachrichten und Zuordnung; neu: `commands` und der Slash-Befehl `/bestellung` mit der Adresse
  `/slack/commands` auf demselben Host wie `/slack/interactions`.

## Entwurf
1. **Datenmodell** (Migration): Tabelle `bestellungen`: `id`, `bestellt_bei` (string), `bestellt_was`
   (text), `bezahlt_mit` (string), `bestellt_am` (datetime), `created_by` (FK users,
   nullOnDelete), `versanddienstleister` (string, nullable), `sendungsnummer` (string, nullable),
   `abgeschlossen_at` (datetime, nullable), `abgeschlossen_by` (FK users, nullOnDelete, nullable),
   `slack_channel` und `slack_message_ts` (nullable), `slack_view_id` (nullable, unique, zur
   Doppelerkennung), Zeitstempel; Activity-Log wie bei den anderen Modellen.
2. **Slack-Eingang**: neuer Endpunkt `POST /slack/commands` (CSRF-Ausnahme, gleiche
   Signaturprüfung; Prüfung in einen gemeinsamen Dienst/Trait ziehen). Öffnet das Formular per
   `views.open` mit `trigger_id` innerhalb von 3 s. Der bestehende `/slack/interactions` wird um
   `view_submission` (neue Bestellung, Sendung eintragen) und Knöpfe (`bestellung:sendung`,
   `bestellung:abschliessen`) erweitert; die Bestellungs-ID reist im Formular als
   `private_metadata` bzw. als Knopf-`value`.
3. **Dienst** `SlackBestellungSync` (Muster `SlackVorgangSync`): `sync(Bestellung)` postet bzw.
   aktualisiert die Nachricht (Blocks, Tracking-Link, abgeschlossen-Ansicht), `slackApi` mit
   Timeout und ohne Ausnahme nach außen.
4. **Antwort an Slack** innerhalb von 3 s (Bestätigung/Fehlertexte "Keine Berechtigung",
   Validierungsfehler im Formular als `response_action: errors`).
5. **App**: `BestellungController` (Liste, Detail, Bearbeiten, Abschließen/Wieder öffnen), Seiten im
   Designsystem, Menüeintrag, Tests (Pest, SQLite und MySQL), Anleitung.
6. **Einrichtung in Slack (manuell, vom Anwender)**: Slash-Befehl `/bestellung` mit Request-URL
   `https://<App-Adresse>/slack/commands`; Scope `commands` ergänzen; Interactivity-URL ist
   bereits gesetzt; Bot in den Bestell-Kanal einladen; Einstellung/`.env` für den Kanal setzen.

## Ansätze
- **A Slash-Befehl, Modal, eine aktualisierte Nachricht je Bestellung, App-Seite (gewählt).**
- **B Stichwort in normalen Nachrichten:** verworfen (mehr Slack-Einrichtung, Fehlalarme, ein Klick
  mehr).
- **C Slack-Workflows ohne App-Code:** verworfen (Sendung nachtragen und Liste offener Pakete
  umständlich, nicht in der App).

## Vom Bauenden zu klären
Aufbau der Blocks, Texte, Tracking-Link-Muster je Dienstleister, Test-Szenarien (Slack-Aufrufe
über `Http::fake`, Signatur im Test erzeugen, MySQL-Lauf), Wiederholungsschutz.

## Erfolg
Eine Bestellung ist in unter einer Minute in Slack erfasst, in der Nachricht und der App-Liste
sichtbar, die Sendung lässt sich später nachtragen, und die Buchhaltung findet die Rechnung im Thread
der Bestell-Nachricht.
