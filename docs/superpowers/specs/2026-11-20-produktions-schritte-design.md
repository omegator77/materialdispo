# Produktion als durchgehender Ablauf (Schrittleiste)

## Intent / Warum
Der Ablauf des Teams ist: Produktion anlegen → VB-Protokoll → Packliste erstellen → packen.
In der App ist das heute ein Springen zwischen Seiten mit wechselnden Namen: "Zuordnen" (Button),
"Materialzuordnung" (Seitentitel), "Packliste" (Menüpunkt = Übersicht aller Produktionen, vom Team
nur als Einstieg zum Packen genutzt), "Packvorgang" (Abhak-Seite). Ziel: ein erkennbar
durchgehender Weg pro Produktion, einheitliche Begriffe.

## Ziele
1. Jede Produktions-Seite zeigt dieselbe Schrittleiste mit Fortschritt.
2. Einheitliche Namen: **Details · VB-Protokoll · Packliste · Packen**.
3. Menüpunkt "Packliste" wird "Packen" und führt schnell zur offenen Packarbeit.

## Nicht-Ziele
- Keine Zusammenlegung der vier Seiten (Ansatz B verworfen), keine neuen Routen/Adressen,
  keine Migration, keine Änderung der Logik (Zuordnung, Packen, Abschließen, PDFs).
- Die Filter der Übersicht (Produktion/Gruppe/Gerät) bleiben vorerst erhalten.

## Anti-Ziel
Neue Begriffe oder Leisten, die nur neben dem Bestehenden stehen. Jeder alte Name wird ersetzt,
nicht ergänzt.

## Entwurf
1. **Komponente** `x-production-steps` (Blade): erhält `production` und `active`
   (`details|vb|packliste|packen`), zeigt vier Schritte als Links mit Status:
   - Details: Name, Zeitraum; Link zur Bearbeitung.
   - VB-Protokoll: ✓ wenn vorhanden, sonst "fehlt" (Link zum Anlegen); nur sichtbar/aktiv für
     Admin/User, Betrachter sehen vorhandene Protokolle.
   - Packliste: Anzahl Geräte (`packlistEntries()->count()`), Link zur Produktionsseite
     (`productions.show`).
   - Packen: `gepackt/gesamt` (`packedItemIds()->count()`), grün bei abgeschlossen
     (`packvorgang_confirmed_at`), Link `packvorgang.show`.
   Die Leiste bricht auf dem Handy um bzw. scrollt horizontal, kein Seitenüberlauf.
2. **Einbau** oben auf: `productions.show`, `packvorgang.show`, `vb-protokoll.show`,
   `vb-protokoll.create/edit` und `productions.edit`.
3. **Umbenennung** (alter Name wird ersetzt): Button "Zuordnen" → "Packliste"; Seitentitel
   "Materialzuordnung" → "Packliste"; "Packvorgang" → "Packen" (Seitentitel, Buttons, Dashboard-
   Anleitung, Befehlspalette); Menüpunkt "Packliste" → "Packen" (Desktop und Mobil); Seitentitel
   der Übersicht "Packliste" → "Packen".
4. **Produktionsliste**: pro Zeile ein Status-Hinweis (gepackt x/y bzw. "abgeschlossen") und die
   Schritte als Links (VB-Protokoll, Packliste, Packen).
5. **Übersicht "Packen"** (`itemprods`, Adresse bleibt): offene Produktionen (Packen nicht
   abgeschlossen) zuerst, jede mit deutlichem Button "Packen" und Fortschritt.

## Ansätze
- **A Schrittleiste, getrennte Seiten (gewählt):** durchgehender Weg, geringes Risiko.
- **B Eine lange Seite:** verworfen; schwere Seite, schlecht fürs Packen am Handy.
- **C Nur umbenennen:** verworfen; ändert das Gefühl des Springens kaum.

## Vom Bauenden zu klären
Optik der Leiste im bestehenden Designsystem (`btn`, `badge-*`), genaue Texte der Zustände,
Tests nach vorhandenem Muster (Pest, SQLite und MySQL, `UiFormsAndDetailsTest` bleibt grün).

## Erfolg
Man findet jeden Schritt einer Produktion von jeder ihrer Seiten mit einem Klick, und im Menü,
in Buttons und Titeln kommt nur noch "Packliste" (Zusammenstellen) und "Packen" (Abhaken) vor.
