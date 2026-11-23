# Mietgeräte: Archiv für abgeschlossene Mietvorgänge, "Erneut mieten"

## Intent / Warum
Gemietete Geräte, deren Mietvorgänge längst vorbei sind, stehen weiter in der Liste "Mietgeräte"
und irritieren. Sie sollen nicht verschwinden (das Gerät wird wieder gemietet und soll nicht neu
erfasst werden), aber nicht unter dem Aktuellen stehen.

## Entscheidungen des Anwenders
- "Vergangen" heißt **abgeschlossen** (Rückgabe bestätigt, `Mietvorgang::isComplete()`), nicht nur
  "Enddatum vorbei". Abgelaufene, nicht zurückgegebene Geräte bleiben oben, markiert
  "Rückgabe offen".
- Archivierte Geräte sollen sich **zurückholen** lassen, ohne das Gerät erneut einzugeben.

## Ziele
1. Mietgeräte teilt sich in **Aktuell** (Tabelle wie bisher) und ein zugeklapptes **Archiv (n)**
   darunter.
2. Archiv = Gerät mit mindestens einem Mietvorgang und **alle** Mietvorgänge abgeschlossen.
   Aktuell = alles andere (auch ohne Mietvorgang: "noch nicht gemietet", kommend, laufend,
   abgelaufen und nicht zurückgegeben: Markierung "Rückgabe offen").
3. Das Archiv zeigt je Gerät Gruppe, Nr., Bezeichnung, Vermieter, "zuletzt <Zeitraum>" und die
   Buttons **Details** und **Erneut mieten**; Checkbox für die bestehende Massenaktion (bestehenden
   Mietvorgang zuordnen). Suche und Gruppenfilter gelten auch fürs Archiv.
4. **Erneut mieten** öffnet "Neuer Mietvorgang" mit dem Vermieter und dem Gerät vorausgewählt
   (Zeitraum gibt man selbst ein). Nach dem Anlegen ist das Gerät automatisch wieder "Aktuell".
5. Die Spalte "Mietzeitraum" der aktuellen Liste zeigt den **laufenden oder nächsten** offenen
   Mietvorgang (sonst den jüngsten abgelaufenen offenen), nicht den frühesten.

## Nicht-Ziele
- Keine Migration, kein gespeichertes Archiv-Flag (alles aus den Mietvorgängen berechnet).
- "Geräte", Produktionsplanung, VB-Vorschlag, Verfügbarkeitsregeln bleiben unverändert (ein
  archiviertes Gerät ist für neue Produktionen erst planbar, wenn wieder gemietet).
- Kein Automatismus, der Mietvorgänge selbst abschließt.

## Anti-Ziel
Dass ein noch im Haus befindliches Gerät (abgelaufen, nicht zurückgegeben) im Archiv verschwindet.

## Risiko (dem Anwender zu nennen)
Abgeschlossen setzt voraus, dass im Mietvorgang "Transport bestätigt" und "bereit zur Rückgabe"
bestätigt werden (UI oder Slack). Werden die Bestätigungen nicht genutzt, landet nichts im Archiv.

## Entwurf
1. **Modell** `Item`: `isRentArchived(): bool` (hat Mietvorgänge, alle `isComplete()`),
   `hasOpenReturn(): bool` (offener Mietvorgang mit `rent_end` < heute) und
   `displayMietvorgang(): ?Mietvorgang` (Regel Ziel 5; bei Archiv der jüngste abgeschlossene nach
   `rent_end`); arbeiten auf der geladenen Relation `mietvorgaenge`.
2. **Controller** `ItemController::rented`: teilt `$items` in aktuell/archiv (`$archived`); `index`
   (Geräte) unverändert.
3. **Views:** Archiv-Abschnitt als `<details>` unter der Mietgeräte-Tabelle (nur wenn nicht leer),
   eigene schlanke Archiv-Zeilen (Tabelle/Karten), Markierung "Rückgabe offen" im Teilpartial
   `_mietzeitraum`.
4. **Erneut mieten:** Link auf `mietvorgaenge.create` mit Query `suppliers_id` und `item_id[]`;
   `MietvorgangController::create` und Formular belegen Vermieter und Gerät vor (unbekannte oder
   unpassende Werte werden ignoriert).

## Ansätze
- **Berechnetes Archiv (gewählt):** nichts zu pflegen, "zurückholen" ergibt sich aus einem neuen
  Mietvorgang.
- **Archiv-Flag am Gerät:** verworfen; müsste manuell oder per Automatik gepflegt werden und
  kann von den Mietvorgängen abweichen.
- **Archiv nach Enddatum:** verworfen (Anwender wählt "abgeschlossen").

## Erfolg
Die Liste "Mietgeräte" zeigt nur, was relevant ist; Altes ist ein Klick entfernt und mit einem
Klick wieder anmietbar.
