# Menü "Miete & Verleih": Mietgeräte getrennt von eigenen Geräten

## Intent / Warum
Gemietete Geräte (mit Vermieter) stehen heute zwischen den eigenen in der Geräteliste. Das Team
will sie als eigene Seite sehen und nicht unter den eigenen Geräten finden. Zugleich liegt das
Mietmanagement (Vermieter, Mietvorgänge, Mieter, Vermietvorgänge) versteckt im Menü
"Verwaltung". Ziel: Eigenes und Fremdes trennen, Miete und Verleih an einem Ort im Menü bündeln.

## Entscheidungen des Anwenders
- Eine **eigene Seite** genügt; Daten bleiben in einer Tabelle (`items.suppliers_id` gesetzt =
  Mietgerät). Mietgeräte sollen nur nicht in den eigenen Geräten auftauchen.
- Neues Hauptmenü **"Miete & Verleih"** (Dropdown) mit zwei Gruppen:
  **Eingang (wir mieten):** Mietgeräte (neue Liste), Vermieter, Mietvorgänge.
  **Ausgang (wir verleihen):** Mieter, Vermietvorgänge.
- "Verwaltung" behält: Gruppen, Gerätetypen, Mailinglisten, Protokoll, Benutzer, Einstellungen.
- Name des Menüs: **"Miete & Verleih"**.

## Ziele
1. **Geräte** (`items.index`) zeigt nur eigene Geräte (ohne Vermieter).
2. **Mietgeräte** (neue Seite) zeigt nur gemietete Geräte: gleiche Tabelle, Filter (Gruppe, Suche),
   Details/Bearbeiten und Massenaktionen wie bei Geräte, dazu Spalten "Vermieter" und
   "Mietzeitraum" (aus den Mietvorgängen des Geräts, sonst "noch nicht gemietet").
3. Das Menü ist umgebaut (Desktop und Mobil), die Befehlspalette findet die neuen Ziele.
4. Der Filter "Herkunft" der Geräteliste entfällt (ersetzt durch die zwei Listen).

## Nicht-Ziele
- Keine Migration, keine Trennung der Daten, keine Änderung an Verfügbarkeit, Zuordnung,
  VB-Vorschlag, Mietvorgängen oder Verleih.
- Geräteauswahl in Produktionen, VB-Vorschlag, Suche, Timeline, Gruppenseiten und Dashboard-Zahlen
  zeigen weiterhin beides (gemietetes Material muss planbar bleiben).
- Kein eigenes Anlege-Formular für Mietgeräte: "Neues Gerät" mit Vermieter im Formular bleibt.

## Anti-Ziel
Dass ein Gerät in keiner Liste oder in beiden erscheint, oder dass nach dem Umbau Links
(Zurück-Buttons, Dashboard, Anleitung) auf ein nicht mehr passendes Ziel zeigen.

## Entwurf
1. **Route/Seite** `GET /items/mietgeraete` → `items.rented` (Rolle wie `items.index`), nutzt die
   bestehende Geräteliste (Controller/View) mit einem Modus "gemietet": Abfrage `whereNotNull
   ('suppliers_id')`, Titel "Mietgeräte", zusätzliche Spalten; `items.index` nutzt `whereNull`.
2. **Navigation** (`layouts/navigation.blade.php`, Desktop und Mobil) und Befehlspalette: Punkt
   "Miete & Verleih" mit den zwei Gruppen; aus "Verwaltung" entfernt: Vermieter, Mietvorgänge,
   Mieter, Vermietvorgänge. Aktiv-Markierung für alle zugehörigen Routen
   (`items.rented`, `suppliers.*`, `mietvorgaenge.*`, `mieter.*`, `vermietvorgaenge.*`).
3. **Aufräumen:** Filter "Herkunft" samt Test entfernen; Anleitung und Dashboard-Texte, die auf
   Menüpunkte unter "Verwaltung" verweisen, anpassen.

## Ansätze
- **Getrennte Ansichten auf einer Tabelle (gewählt):** kleiner Umbau, nichts bricht.
- **Eigene Tabelle für Mietgeräte:** verworfen; wäre ein großer Umbau ohne Nutzen für den
  Anwender (Eine-Seite-Wunsch).
- **Verleih unter "Produktionen":** verworfen; würde den meistgenutzten Menüpunkt verlängern
  und Fremdverleih fachlich mit eigenen Produktionen mischen.

## Vom Bauenden zu klären
Aufbau der Spalten/Karten der Mietgeräte-Liste im bestehenden Designsystem, Test-Szenarien
(Pest, SQLite und MySQL), Behandlung von Bulk-Auswahl und Sortierlinks auf der neuen Seite.

## Erfolg
Mietgeräte erscheinen nur unter "Miete & Verleih → Mietgeräte", eigene nur unter "Geräte", und
alle vier Mietverwaltungs-Seiten sind über das neue Menü erreichbar.
