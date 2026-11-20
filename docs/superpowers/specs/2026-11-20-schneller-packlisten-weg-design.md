# Schnellerer Weg zur Packliste (Stufe C, danach Stufe A)

## Intent / Warum
Das Team erstellt Packlisten bisher in Excel (Vorlage oder alte Liste, Material per
Ausschneiden/Einfügen aus einem Lager-Blatt) und notiert Vermietungen als Freitext in Slack.
Die App funktioniert, wird aber kaum genutzt, weil sie nicht schneller ist. Ihr Vorteil, den
Excel nicht bietet, ist die automatische Erkennung von Doppelbuchungen und fehlenden Mieten.
Ziel: Eine Packliste ist in der App mindestens so schnell fertig wie in Excel.

## Ist-Zustand (Befund)
- "Neue Produktion" speichert und springt zurück in die Produktionsliste, nicht zur neuen
  Produktion. Material wird erst nach einem weiteren Klick zugeordnet.
- Daten werden als Text (TT.MM.JJJJ) getippt, Ende muss separat eingegeben werden.
- Die Vorlagenwahl ("optional") ist ein Suchfeld, ältere Produktionen sind schwer zu finden.
- Die schnellen Wege existieren bereits (Text-Schnelleingabe, "Material übernehmen aus
  ähnlicher Produktion", Vorschläge, Kamerazüge automatisch, Offenen Bedarf zuordnen), sind
  aber in der Produktionsseite hinter Meldungen, Mietvorgängen und Abgleich versteckt.

## Ziele
1. Von "Neue Produktion" bis zur gefüllten Packliste: höchstens drei Schritte.
2. Wiederkehrende Produktion: ein Klick "Duplizieren", nur Termine anpassen.
3. Die Schnelleingabe ist auf der Produktionsseite das Erste, was man sieht.

## Nicht-Ziele
- Keine neue Zwei-Spalten-Ansicht (das ist Stufe A, eigenes Projekt danach).
- Keine Datenmodell-Änderungen, keine Migrationen.
- Keine Änderung der Verfügbarkeits-, Miet- und Packlogik.

## Anti-Ziel
Eine Oberfläche, die hübscher ist, aber nicht schneller. Maßstab ist die Schrittzahl und
die Zeit für eine echte Produktion, nicht die Optik.

## Entwurf (Stufe C)
1. **Nach dem Anlegen direkt zur Produktion** (Materialzuordnung) statt zur Liste; ohne
   Vorlage wird die Schnelleingabe fokussiert.
2. **Formular**: Vorlage als Auswahl der letzten Produktionen (anklickbar) plus Suche, nicht
   mehr "optional" versteckt. Ende-Datum wird mit dem Beginn vorbelegt (eintägige Produktion
   ist der häufige Fall), bleibt änderbar.
3. **Duplizieren**: Button in Produktionsliste und Produktionsseite, öffnet das Formular mit
   Vorlage und Kopie-Namen, Termine leer.
4. **Produktionsseite neu geordnet**: Schnelleingabe und "Aus ähnlicher Produktion
   übernehmen" oben; Mietvorgänge, Vermietvorgänge und VB-Abgleich darunter. Leere
   Produktion zeigt drei große Wege: Text einfügen, aus alter Produktion übernehmen,
   einzeln wählen.

## Ansätze (verworfen)
- **A zuerst** (Lager links / Packliste rechts): größter Umbau, sinnvoll erst, wenn der
  kurze Weg im Alltag genutzt wird.
- **B Text zuerst als Hauptweg**: steckt über Punkt 1 und 4 zu großen Teilen in C.
Gewählt: C, danach A (Entscheidung des Nutzers).

## Vom Bauenden zu klären
Genaue Platzierung und Texte innerhalb dieser Vorgaben; Tests nach vorhandenem Muster
(Pest, SQLite und MySQL).

## Erfolg
Das Team legt die nächste echte Produktion in der App an. Bleibt es bei Excel, wird Stufe A
mit den dann genannten Gründen geplant.
