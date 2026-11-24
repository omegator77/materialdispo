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
- Keine Dateispeicherung in der App. Die **Buchhaltung lädt die Rechnung aus Slack herunter und
  archiviert sie selbst**; ein automatisches Löschen der Datei in Slack ist nicht nötig (der
  Free-Tarif löscht Dateien nach spätestens einem Jahr ohnehin selbst).
- Vier **Stufen** je Bestellung: **Bestellt**, **Unterwegs** (Sendungsnummer eingetragen),
  **Rechnung liegt vor**, **Bezahlt & abgelegt**. Die letzte Stufe setzt nur die **Buchhaltung**
  (2 bis 3 Personen, alle haben einen App-Benutzer); alle anderen Schritte darf jeder Benutzer und
  Admin, auch das Hochladen der Rechnung und der Klick "Rechnung liegt vor" (nicht nur der Besteller).

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
   Besteller, **aktuelle Stufe**; Knöpfe je nach Stufe; Hinweis "Rechnung als Datei in den Thread
   dieser Nachricht hochladen".
3. **Sendung eintragen** (Knopf) öffnet ein Formular (Versanddienstleister, Sendungsnummer),
   aktualisiert dieselbe Nachricht mit Tracking-Link (Stufe **Unterwegs**). Eine eingetragene
   Sendung lässt sich überschreiben.
4. **Rechnung liegt vor** (Knopf, jeder Benutzer/Admin): Stufe **Rechnung liegt vor**; der Bot
   spricht die Buchhaltung im Thread an (Erwähnung der Buchhaltungs-Benutzer, per E-Mail ihren
   Slack-Konten zugeordnet).
5. **Bezahlt & abgelegt** (Knopf, nur Benutzer mit Häkchen **Buchhaltung**): Stufe **Bezahlt &
   abgelegt** (Zeitpunkt, wer); Nachricht wird kompakt ("✅ bezahlt & abgelegt").
6. **Erinnerung:** Steht eine Bestellung länger als 30 Tage auf "Rechnung liegt vor", erinnert der
   Bot die Buchhaltung im Thread, danach alle 7 Tage, bis die Stufe erreicht ist (Schutz davor, dass
   die Datei in Slack nach einem Jahr verschwindet, bevor sie abgelegt ist).
7. **App-Seite "Bestellungen"**: Liste mit Datum, Besteller, bei, was, bezahlt mit, Sendung
   (Link), **Stufe**; Filter "Offen / Bezahlt & abgelegt / Alle" (Standard Offen = alles außer
   Bezahlt & abgelegt) und Suche; Detail- und Bearbeiten-Seite (Tippfehler korrigieren, Sendung
   nachtragen, Stufen weiterschalten bzw. zurücknehmen); jede Änderung aktualisiert die
   Slack-Nachricht. Im Menü unter "Verwaltung" in neuer Gruppe "Einkauf".
8. **Benutzerverwaltung:** Häkchen **Buchhaltung** am Benutzer (nur Admin setzbar).

## Nicht-Ziele
- Keine Speicherung oder Erkennung der Rechnung in der App (kein Mithören von Nachrichten oder
  Datei-Uploads; "Rechnung liegt vor" ist ein bewusster Klick) und **kein Löschen** von Dateien in
  Slack.
- Kein Abruf des Sendungsstatus bei den Paketdiensten, keine Summen/Exporte; Erinnerungen nur wie
  unter Ziel 6.
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
- **Die Stufen werden durch Klicks gesetzt** (kein automatisches Erkennen von Dateien, kein
  Mithören). Irrtum: Bestellungen bleiben auf einer Stufe stehen, wenn niemand klickt; die
  Erinnerung fängt nur die Stufe "Rechnung liegt vor" ab.
- **Erinnerungsfrist** 30 Tage, danach alle 7 Tage; als Konstanten, nicht einstellbar.
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
- **Wer darf was:** Sendung eintragen und "Rechnung liegt vor": jeder Admin/Benutzer, nicht nur
  der Besteller; "Bezahlt & abgelegt": nur Benutzer mit Häkchen Buchhaltung (auch Admins ohne das
  Häkchen nicht). Zurücknehmen einer Stufe gibt es nur in der App (Buchhaltung für die letzte
  Stufe, sonst jeder Admin/Benutzer); die Slack-Nachricht bekommt dabei ihre Knöpfe zurück.
- **Zeitzone:** Datum und Uhrzeit nach `APP_TIMEZONE` (in `docs/betrieb.md` für Produktion als
  `Europe/Berlin` vorgesehen); die App formatiert in dieser Zone.
- **Längen:** "Bestellt was" höchstens 2000 Zeichen, die anderen Felder höchstens 255; Slack-Texte
  werden maskiert (`SlackVorgangSync::escape`), die Sendungsnummer wird getrimmt und im Link
  URL-kodiert.
- **Protokoll:** alle Aktionen (anlegen, Sendung, Rechnung liegt vor, bezahlt & abgelegt, Zurücknehmen) landen im Activity-Log
  mit dem zugeordneten Benutzer als Verursacher.
- **Slack-Einrichtung:** vorhandene Scopes (`chat:write`, `users:read`, `users:read.email`) genügen
  für Nachrichten und Zuordnung; neu: `commands` und der Slash-Befehl `/bestellung` mit der Adresse
  `/slack/commands` auf demselben Host wie `/slack/interactions`.

## Entwurf
1. **Datenmodell** (Migration): Tabelle `bestellungen`: `id`, `bestellt_bei` (string), `bestellt_was`
   (text), `bezahlt_mit` (string), `bestellt_am` (datetime), `created_by` (FK users,
   nullOnDelete), `versanddienstleister` (string, nullable), `sendungsnummer` (string, nullable),
   `rechnung_vorhanden_at` und `rechnung_vorhanden_by` (nullable, FK users nullOnDelete),
   `abgelegt_at` und `abgelegt_by` (nullable, FK users nullOnDelete), `letzte_erinnerung_at`
   (nullable),
   `slack_channel` und `slack_message_ts` (nullable), `slack_view_id` (nullable, unique, zur
   Doppelerkennung), Zeitstempel; Activity-Log wie bei den anderen Modellen. Die **Stufe** wird aus den Feldern berechnet
   (Sendungsnummer, `rechnung_vorhanden_at`, `abgelegt_at`), nicht gespeichert. Zweite Migration:
   `users.ist_buchhaltung` (boolean, Standard false) und Häkchen im Benutzerformular.
2. **Slack-Eingang**: neuer Endpunkt `POST /slack/commands` (CSRF-Ausnahme, gleiche
   Signaturprüfung; Prüfung in einen gemeinsamen Dienst/Trait ziehen). Öffnet das Formular per
   `views.open` mit `trigger_id` innerhalb von 3 s. Der bestehende `/slack/interactions` wird um
   `view_submission` (neue Bestellung, Sendung eintragen) und Knöpfe (`bestellung:sendung`, `bestellung:rechnung`, `bestellung:abgelegt`) erweitert; die Bestellungs-ID reist im Formular als
   `private_metadata` bzw. als Knopf-`value`; Knöpfe: `bestellung:sendung`, `bestellung:rechnung`, `bestellung:abgelegt`.
   Ein geplanter Befehl `bestellungen:erinnern` (täglich, im vorhandenen Scheduler) verschickt die
   Erinnerungen.
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
sichtbar, die Sendung lässt sich später nachtragen, die Buchhaltung findet die Rechnung im Thread
und setzt am Ende "Bezahlt & abgelegt"; liegt eine Rechnung zu lange unbearbeitet, wird erinnert.
