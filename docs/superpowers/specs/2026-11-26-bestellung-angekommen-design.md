# Bestellungen: Knopf "Sendung angekommen" mit optionaler Notiz

## Intent / Warum
In der Slack-Nachricht einer Bestellung soll **sichtbar** sein, dass das Paket da ist. Heute
endet die Sichtbarkeit bei "Unterwegs"; ob etwas angekommen ist, weiß nur, wer es ausgepackt hat.
Beim Klick kann optional eine **Notiz** erfasst werden (Beispiel: Lagerort).

## Entscheidungen des Anwenders
- Ansatz **A**: "Angekommen" ist eine **eigene Information neben den vier Stufen**, keine fünfte
  Stufe. Die Rechnung kommt oft vor dem Paket; jede Reihenfolge muss funktionieren.
- Der Klick auf **Sendung angekommen** öffnet ein Formular mit einem **optionalen** Notizfeld
  (z. B. Lagerort).
- Sichtbar in der Slack-Nachricht (Zeile mit Datum, Person und Notiz).

## Meine Annahmen (im Gespräch vorgeschlagen, nicht widersprochen)
- Den Knopf drücken darf jeder Benutzer und Admin (wie "Rechnung liegt vor"); Betrachter nicht.
- In der App lässt sich die Ankunft **zurücknehmen** (falls versehentlich geklickt).
- Die App-Seite zeigt die Ankunft ebenfalls.
- Keine zusätzliche Thread-Nachricht, keine Erwähnung; die Zeile in der Bestellnachricht genügt.

## Ziele
1. Neue Spalten an `bestellungen`: `angekommen_at` (dateTime, null), `angekommen_by` (FK users,
   nullOnDelete), `angekommen_notiz` (string 255, null).
2. Modell: `markAngekommen(User, ?string notiz): bool` (atomar, nur wenn noch nicht angekommen;
   Notiz getrimmt, leer = null) und `revertAngekommen(User): bool`; je ein lesbarer Protokolleintrag
   mit dem handelnden Benutzer.
3. Slack: Knopf **Sendung angekommen** an der Nachricht, solange `angekommen_at` leer ist und die
   Bestellung nicht abgelegt ist; Klick öffnet das Formular (Titel "Sendung angekommen", optionales
   Feld "Notiz (optional), z. B. Lagerort", max. 255 Zeichen); Absenden setzt die Ankunft und
   aktualisiert die Nachricht um die Zeile `📦 Angekommen am TT.MM.JJJJ HH:MM Uhr von Name — Notiz`.
   Die Zeile bleibt auch nach "Bezahlt & abgelegt" stehen. Der Knopf verschwindet nach dem Klick.
4. App-Seite: Detailseite zeigt Ankunft (Zeit, Person, Notiz) oder einen Knopf **Sendung
   angekommen** mit optionalem Notizfeld; **Ankunft zurücknehmen**; die Liste kennzeichnet
   angekommene Sendungen; die Notiz ist im Bearbeiten-Formular änderbar, sobald angekommen.
5. Anleitung und Testliste ergänzen.

## Nicht-Ziele
- Keine fünfte Stufe; `stufe()`, Erinnerung (30/7 Tage), Filter "Offen" bleiben unverändert.
- Keine Erwähnung oder Thread-Nachricht, keine Statusabfrage beim Paketdienst.
- Kein Pflichtfeld, kein Foto/Datei-Upload.

## Anti-Ziele
- Ein zweiter Klick darf eine vorhandene Ankunft (Zeit, Person, Notiz) nie überschreiben.
- Betrachter und unbekannte Slack-Konten ändern nichts (gleiche Ablehnung wie bei den anderen Knöpfen).
- Slack-Ausfall darf das Speichern nie abbrechen (wie bisher: Sync nach der Antwort).

## Constraints
- Laravel 11, Pest, MySQL 8.4 (Produktion), keine Queue: Sync mit `dispatch(...)->afterResponse()`.
- Eine additive Migration, Datensicherung vorher (Runbook ergänzen). Keine neuen Slack-Scopes.
- Slack-Textlimits (Section 3000 Zeichen; Notiz vor dem Maskieren kürzen), Maskierung wie bisher
  über `SlackVorgangSync::escape`.

## Approaches
- **A (gewählt):** eigene Information `angekommen_at` neben den Stufen. Passt zu beliebiger
  Reihenfolge von Rechnung und Paket, kleinste Änderung.
- **B:** fünfte Stufe zwischen Unterwegs und Rechnung. Verworfen: Stufe wäre falsch oder übersprungen,
  wenn die Rechnung vor dem Paket kommt.

## Offen für den Builder
- Genaue Gestaltung der Zeile und der App-Anzeige im Rahmen der Design-System-Klassen.
