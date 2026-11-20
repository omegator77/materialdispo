# Packliste aus dem VB-Protokoll vorschlagen

## Intent / Warum
Im VB-Protokoll steht, was die Produktion braucht, als **Typ** ("LDX 86 Universe"), nicht als
konkretes Gerät. Heute muss das Team die Kamerazüge und das übrige Material trotzdem von Hand
anlegen. "Kamerazüge automatisch" rät das Zubehör nur aus früheren Zügen, und der Button
"Offenen Bedarf zuordnen" überspringt die Kamerazeilen komplett. Ziel: Aus dem VB-Protokoll
einen **Vorschlag** für Kamerazüge und Material ableiten, mit je einem **freien konkreten
Gerät** des verlangten Typs vorausgefüllt. Das Team prüft, tauscht bei Bedarf und übernimmt.

## Ist-Zustand (Befund)
- VB-Anforderungen (`vb_protokoll_anforderungen`): je Zeile Gruppe oder Gerätetyp plus Anzahl.
  Kamerazeilen haben `cam_number` (freier Text, z. B. "1" oder "Cam 1") und Typen für Kamera
  (`geraetetyp_id`), Objektiv, Stativ, Stativkopf, Adapter.
- `SmartAssignmentService::openVbNeeds()` liefert den offenen Bedarf **ohne** Kamerazeilen
  (Soll je Typ/Gruppe minus Geräte der Packliste). `plan()`/`planFromPool()` wählen freie
  Geräte (`ranked()`: Eigentum vor Miete, niedrige Nummer), `apply()` ordnet per
  `ProductionAssignmentService::attachMany()` zu.
- `CameraSetupService` legt Kamerazüge an (`CameraConfigService::create()` prüft Verfügbarkeit
  und, seit kurzem, dass ein Gerät je Produktion nur an einer Stelle steht).
- Muster für Vorschau + Übernehmen gibt es schon: Schnelleingabe (Vorschau) und Import.
- Freitext-Blöcke im VB-Protokoll sind reiner Text.

## Ziele
1. Ein Button **"Packliste aus VB-Protokoll vorschlagen"** zeigt eine Vorschau mit
   - einem Kamerazug je offener VB-Kamerazeile (Kamera, Objektiv, Stativ, Kopf, Adapter, je ein
     freies Gerät des verlangten Typs),
   - dem übrigen offenen Bedarf (Typ/Gruppe × Anzahl) mit freien Geräten.
2. Pro Position kann das Gerät per Auswahl getauscht (freie Geräte desselben Typs) oder
   weggelassen werden; Lücken ("keins frei") sind markiert.
3. "Übernehmen" legt die gewählten Kamerazüge an und ordnet das Material zu.
4. Der Vorschlag ist wiederholbar und füllt nur, was **fehlt**; Vorhandenes bleibt unberührt.

## Nicht-Ziele
- Kein Auswerten von Freitext. Nur strukturierte Anforderungen (Typ/Gruppe/Anzahl, Kamerazeilen).
- Kein Ändern oder Löschen bestehender Kamerazüge oder Zuordnungen.
- Kein Raten von Zubehör aus der Historie: Was das VB-Protokoll nicht verlangt, bleibt leer.
- Keine Migration, kein neuer Mietvorgang (Mietgeräte ohne Mietvorgang erscheinen mit
  Warnung, wie bei der normalen Zuordnung).

## Anti-Ziel
Dass die App ohne Sicht des Anwenders Geräte vergibt. Ein Vorschlag ändert nichts, bis
"Übernehmen" gedrückt wird, und die Übernahme prüft jedes Gerät erneut.

## Regeln
- Gerätewahl je Slot: freie Geräte (nicht in der Produktion vergeben, im Zeitraum verfügbar,
  nicht in diesem Vorschlag schon verplant), sortiert mit `ranked()`.
- Gerät nur an einer Stelle je Produktion (bestehende Regel, gilt auch für den Vorschlag).
- Welche VB-Kamerazeilen "offen" sind: je Kamera-Typ zählen die VB-Kamerazeilen, minus die
  vorhandenen Kamerazüge der Produktion mit einer Kamera dieses Typs; die offenen Zeilen (in
  Reihenfolge der VB-Zeilen) erhalten einen neuen Zug. Name des Zugs: `Cam {n}` mit der
  nächsten freien Nummer (`CameraSetupService::nextCamNumber()`), nicht der VB-Text.
- Eine Kamerazeile ohne freie Kamera des Typs ergibt keinen Zug, sondern eine markierte Lücke.
- Übernahme in einer Transaktion unter Geräte-Sperre; jedes Gerät wird erneut geprüft, was
  nicht mehr geht, wird mit Grund gemeldet statt halb übernommen.

## Entwurf
1. **Service** `VbVorschlagService` mit `propose(Production): array` (Kamerazüge und übriger
   Bedarf, je Slot gewähltes Gerät, Alternativen, Lücken, Warnungen) und
   `apply(Production, array $auswahl): array` (angelegte Züge, zugeordnete Geräte, übersprungene
   mit Grund). Baut auf `SmartAssignmentService`, `CameraConfigService`,
   `ProductionAssignmentService` und `ItemAvailabilityService` auf.
2. **Routen/Controller** (nur Rolle admin/user): GET Vorschau und POST Übernehmen je Produktion.
3. **Oberfläche**: Vorschau-Seite im Designsystem (Karten je Kamerazug, Liste für übriges
   Material, je Slot Auswahlfeld, Haken "übernehmen"); Einstiegspunkte: Leer-Zustand und
   Schnelleingabe-Karte der Packliste-Seite (ersetzt dort "Offenen Bedarf automatisch
   zuordnen"; die alte Route bleibt) und die VB-Protokoll-Ansicht. Ohne VB-Protokoll oder ohne
   offene Anforderungen: kein Button bzw. Hinweis "Alles gedeckt".

## Ansätze
- **A Ein Klick, direkt anlegen:** verworfen, weil die App ohne Sicht des Anwenders entscheidet.
- **B Vorschlag prüfen, dann übernehmen (gewählt).**
- **C Kamerazug je VB-Zeile einzeln über das Formular:** verworfen, ein Klick pro Zug und das
  übrige Material bleibt bei der alten Funktion.

## Vom Bauenden zu klären
Aufbau der Vorschau im bestehenden Designsystem, Texte, Test-Szenarien (Pest, SQLite und MySQL;
feste Gruppen-IDs 1 bis 5 für Kamerazug-Formulare beachten, siehe
`CameraConfigDuplicateItemTest`).

## Erfolg
Aus einem VB-Protokoll mit Kamerazeilen und weiterem Material entsteht mit einem Klick auf den
Button und einer Bestätigung die vollständige Packliste samt Kamerazügen, mit sichtbaren
Lücken statt stillem Auslassen.
