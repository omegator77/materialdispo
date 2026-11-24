# Material Dispo — Quick Tutorial

Diese Anleitung zeigt, was die Anwendung kann und wie man sie im Alltag bedient. Sie richtet sich an alle Nutzer:innen, unabhängig von technischem Vorwissen.

## Wofür ist die App da?

Material Dispo verwaltet das Equipment für Broadcast-/Live-Produktionen: TV-Kameras (z. B. Grass Valley LDX), Objektive, Stative, Monitore, Intercom- und Kabeltechnik. Sie hilft dabei,

- zu sehen, welches Gerät gerade wo im Einsatz ist,
- Produktionen mit dem passenden Material auszustatten,
- Mietgeräte, die man selbst ausleiht oder verleiht, sauber zu tracken,
- Packlisten zu erstellen und beim Verladen abzuhaken.

## Rollen: Wer darf was?

Es gibt drei Rollen, die vom Admin vergeben werden (siehe **Verwaltung → Benutzer**):

| Rolle | Rechte |
|---|---|
| **Betrachter** | Nur lesen: Dashboard, Geräte, Produktionen, Timeline, Packen, PDFs ansehen |
| **Benutzer** | Wie Betrachter, plus: Geräte/Produktionen/Vorgänge anlegen, bearbeiten, Haken setzen (z. B. „Packen abschließen") |
| **Admin** | Wie Benutzer, plus: Benutzerverwaltung und Einstellungen |

Zusätzlich gibt es am Benutzer das Häkchen **Buchhaltung** (setzt nur der Admin): Nur diese Personen dürfen Bestellungen auf „Bezahlt & abgelegt" setzen.

Falls ein Button fehlt, den man erwarten würde — meistens liegt es an der Rolle, nicht an einem Fehler.

## Die Hauptnavigation

Oben in der Leiste:

- **Dashboard** — Übersicht über aktuelle Belegung und offene Vorgänge
- **Geräte** — die eigenen Geräte (Eigentum); gemietete Geräte stehen unter *Miete & Verleih → Mietgeräte*
- **Produktionen** — laufende und geplante Drehs/Projekte
- **Packen** — Gesamtübersicht aller Produktionen mit ihrem Material
- **Timeline** — Zeitstrahl, wer welches Gerät wann belegt
- **Miete & Verleih** (Dropdown) — *Eingang* (was wir mieten): Mietgeräte, Vermieter, Mietvorgänge; *Ausgang* (was wir verleihen): Mieter, Vermietvorgänge
- **Verwaltung** (Dropdown) — Stammdaten (Gruppen, Gerätetypen, Mailinglisten), *Einkauf → Bestellungen*, das Protokoll sowie (für Admins) Benutzer und Einstellungen

## Dashboard: der Einstieg

Nach dem Login landet man auf dem Dashboard. Es zeigt auf einen Blick:

- **Kennzahlen** oben: „Aktive Vorgänge" (heute laufende Produktionen/Miet-/Vermietvorgänge), „Geräte gesamt" (inkl. Anteil Mietmaterial), „Heute gebucht" sowie die letzten Aktionen aus dem Protokoll.
- **Laufende Vorgänge** — was gerade heute läuft.
- **Nächste Vorgänge** — was als Nächstes ansteht.
- **Offene Vorgänge** — Miet-/Vermietvorgänge und Produktionen, bei denen noch nicht alle Status-Haken gesetzt sind, mit direktem Link zum jeweiligen Vorgang.
- **Zuletzt angelegte Produktionen** als Tabelle mit Start-/Enddatum und Link zum Öffnen.

## Geräte, Gerätetypen und Gruppen

Jedes Gerät (z. B. „LDX 86N WorldCam", Nr. 22) gehört zu:

- einer **Gruppe** (z. B. „Kameras", „Optiken", „Stativ", „Audio", „Intercom") — dient zur Sortierung und zum Filtern in Timeline und Packen,
- einem **Gerätetyp** (z. B. „LDX 86N WorldCam" bei Kameras oder „Canon 22 Fach" bei Objektiven) — wird u. a. für Materialanforderungen im VB-Protokoll genutzt.

Ein Gerät mit eingetragenem Vermieter zählt als Mietmaterial und steht in der Liste **Mietgeräte** (mit Vermieter und Mietzeitraum, oder „noch nicht gemietet“, solange kein Mietvorgang angehängt ist) statt unter **Geräte**; trägt man den Vermieter aus, wandert es zurück.

**Archiv und „Erneut mieten“:** Sind *alle* Mietvorgänge eines Mietgeräts abgeschlossen (Rückgabe im Mietvorgang bestätigt, in der App oder per Slack), wandert das Gerät in das zugeklappte **Archiv** unter der Liste (mit „zuletzt“-Zeitraum). Mit **Erneut mieten** öffnet man „Neuer Mietvorgang“ mit Vermieter und Gerät schon eingetragen — nur den Zeitraum gibt man ein, danach steht das Gerät wieder in der aktuellen Liste. Ein Gerät, dessen Mietvorgang abgelaufen, aber noch nicht zurückgegeben ist, bleibt oben und trägt die Markierung **Rückgabe offen**. Ohne die Bestätigung der Rückgabe landet ein Gerät nie im Archiv.

Auf der Geräte-Detailseite sieht man Stammdaten, ob es sich um **Eigenmaterial** oder **Mietmaterial** (inkl. Vermieter, z. B. „Pixmade") handelt, und in welchen Produktionen bzw. Miet-/Vermietvorgängen das Gerät aktuell steckt. Kameras, Monitore und Objektive haben zusätzliche technische Detailfelder.

Neue Geräte, Gerätetypen und Gruppen legt man über **Verwaltung** an bzw. über die „+"/„Neu"-Buttons auf den jeweiligen Listenseiten an.

## Produktionen: Packliste erstellen

Eine **Produktion** ist ein Dreh/Projekt mit Bezeichnung sowie Start- und Enddatum. Auf der Detailseite kann man:

1. **Material hinzufügen** — über die Suche einzelne Geräte zuordnen. Bei Kameras kann man statt „Standard hinzufügen" auch **„Konfigurieren"** wählen, um einen kompletten Kamerazug (Kamera + Objektiv + Stativ + Kopf + Adapter) zusammenzustellen.
2. Verknüpfte Miet-/Vermietvorgänge einsehen (Link „Bearbeiten →").
3. Ein **VB-Protokoll** (Vorbesprechungsprotokoll) anlegen — dort trägt man Kunde, Ort, Zeitplan, Crew und die benötigten Geräte/Mengen ein. Die App vergleicht automatisch Bedarf mit tatsächlich zugeordnetem/gepacktem Material und zeigt eine rot/gelb/grün-Ampel.
4. Die Packliste aus dem VB-Protokoll **vorschlagen** lassen: „Packliste aus VB-Protokoll vorschlagen" zeigt eine Vorschau mit Kamerazügen und Material, die man prüfen, anpassen und mit „Übernehmen" zur Produktion hinzufügen kann.
5. Die Materialliste als **PDF exportieren**.

Oben auf den Produktionsseiten zeigt die **Schrittleiste** (Details · VB-Protokoll · Packliste · Packen) den Stand der Produktion und führt mit einem Klick zum jeweiligen Schritt.

## Schneller arbeiten: Suche, Schnellerfassung, Massenaktionen

**Suche & Befehle (Strg/⌘ + K oder `/`)** — überall verfügbar. Tippen sucht Geräte (Name, Nummer, auch „ldx 22"), Produktionen, Vermieter, Mieter und Gruppen. Geräte zeigen sofort, ob sie *frei* sind oder wann die nächste Buchung ansteht. Befehle wie „Neue Produktion" oder „Geräte schnell erfassen" finden sich über ihre Stichworte.

**Geräte schnell erfassen** (Geräte → „Mehrere erfassen") — mehrere Geräte in einem Schritt anlegen:

- pro Zeile Gerätetyp + Anzahl **oder** Nummernbereich (`21-23, 30`); ohne Angabe wird ab der nächsten freien Nummer der Gruppe weitergezählt,
- optional gleich mit Vermieter und Mietzeitraum — alle Geräte einer Zeile landen im selben Mietvorgang,
- oder einfach Text eintippen: `3x LDX 86N #21-23` / `5 Stativ` → „Zeilen erkennen". Typ und Gruppe werden automatisch (auch bei Tippfehlern) erkannt.

**Massenaktionen in der Geräteliste** — Geräte per Checkbox markieren (Shift-Klick wählt einen Bereich, Kopf-Checkbox alle sichtbaren). Unten erscheint eine Leiste: markierte Geräte in einem Zug einer Produktion, einem Miet-/Vermietvorgang zuordnen oder in eine andere Gruppe verschieben. Nicht verfügbare Geräte werden übersprungen und mit Grund gemeldet. Das funktioniert auch am Handy (Checkbox oben rechts an jeder Karte, „Alle sichtbaren auswählen") und in der **Timeline**: dort lassen sich einzelne Geräte oder per Checkbox im Gruppenkopf eine ganze Gruppe markieren — die Leiste ist dieselbe.

**Produktion: „Schnell hinzufügen"** — Bedarf als Text eingeben (`3x LDX, 2 Stativ`). Eine Live-Vorschau zeigt, welche konkreten Geräte gewählt werden (Eigentum vor Miete, niedrige Nummer zuerst), was blockiert ist und warum, und welche Alternativen es in der Gruppe gibt.

**VB-Protokoll aus Vorlage** — beim Anlegen eines Protokolls lässt sich eine frühere Produktion übernehmen (ähnlichste zuerst): Bedarf, Textblöcke und Crew-Stärken werden kopiert, Kunde und Ort bleiben leer.

**Schnell hinzufügen bei Miet- und Vermietvorgängen** — dieselbe Texteingabe wie bei Produktionen. Bei einem Mietvorgang kommen nur Geräte des jeweiligen Vermieters infrage; Verfügbarkeit wird nach den Regeln des Vorgangs geprüft.

**Intelligente Hilfen** (regelbasiert, ohne externe KI):

- **Handlungsbedarf** auf dem Dashboard: Doppelbuchungen/Mietfenster-Konflikte mit Ein-Klick-„Tauschen" gegen ein freies Gerät, offene Positionen aus dem VB-Protokoll (der Link zur Vorschau „Packliste vorschlagen" erscheint, sobald offener Bedarf besteht), leere Produktionen kurz vor Start (mit Vorschlag einer ähnlichen Vorlage), offene Packvorgänge.
- **Packliste aus VB-Protokoll vorschlagen** auf der Produktionsseite (Schnelleingabe, leere Produktion) und im VB-Protokoll: zeigt eine Vorschau mit Kamerazügen und Material zum Übernehmen, sobald das VB-Protokoll Anforderungen enthält.
- **Wird oft zusammen gebucht**: Vorschläge aus früheren Produktionen mit demselben Material.

## Packen: Verladen abhaken

Das **Packen** ist die praktische Checkliste beim Einladen des Materials für eine Produktion (erreichbar über den Button „Packen" in der Übersicht oder in der Schrittleiste der Produktion):

- Jedes benötigte Gerät (gruppiert nach Kamerazug bzw. Gerätegruppe) hat eine Checkbox. Ein Klick markiert es als gepackt (mit Name und Uhrzeit).
- Ein Fortschrittsbalken zeigt „X von Y Geräten im Rüstwagen".
- Sind alle Geräte gepackt, kann man mit **„Packen abschließen"** das Packen sperren. Fehlt noch etwas, muss man das bestätigen („Trotz fehlender Geräte abschließen").
- Ein abgeschlossenes Packen lässt sich über **„Wieder öffnen"** erneut bearbeiten.
- Über **„PDF-Checkliste"** lässt sich die Liste ausdrucken.

## Packen: der Überblick über alles

Der Menüpunkt **Packen** (im Hauptmenü) zeigt die Produktionen, die gepackt werden müssen — offene zuerst (unbestätigte vor bestätigten, dann nach Startdatum) — jeweils mit ihren Kamerazügen und weiteren Geräten. Jede Produktion hat einen Button **„Packen"** mit Fortschrittsanzeige; von hier aus gelangt man auch zum PDF-Export und — sobald das Packen abgeschlossen ist — zum **Abgleich-Report** (Soll aus dem VB-Protokoll vs. tatsächlich gepacktes Material).

## Timeline: Wer hat wann was?

Die **Timeline** zeigt alle Geräte als Zeitstrahl (Gantt-Ansicht), gruppiert nach Gruppe. Farben zeigen die Art der Belegung:

- **Blau** = Produktion
- **Gelb/Orange** = eingehende Miete (Mietvorgang)
- **Lila** = ausgehende Vermietung (Vermietvorgang)

Ein Klick auf einen Balken führt direkt zur jeweiligen Produktion bzw. zum Vorgang. Über die Filter lassen sich Zeitraum, Gruppe sowie „Nur gebucht"/„Nur frei" einschränken, um freie Geräte für einen Zeitraum zu finden.

## Mietvorgänge (eingehende Miete)

Ein **Mietvorgang** bildet ab, dass man selbst Geräte bei einem **Vermieter** anmietet. Neben Stammdaten (Zeitraum, zugeordnete Geräte) gibt es vier Status-Schritte, die man nacheinander per Button abhakt (jeweils mit „Wieder öffnen" rückgängig zu machen):

1. **Hinweg: Angenommen** — Ware ist beim Vermieter abgeholt/angenommen worden
2. **Geprüft** — Material wurde kontrolliert
3. **Zur Rückgabe fertig** — bereit für den Rückversand
4. **Rückweg: Übergeben** — an den Vermieter zurückgegeben

Zusätzlich lassen sich Transport-Logistik (Hinweg/Rückweg-Beschreibung, automatische Benachrichtigung des Vermieters) und Erinnerungen (X Tage vor Beginn/Ende, per Mailingliste) hinterlegen. Über **„Materialliste (PDF)"** lässt sich die Geräteliste des Vorgangs exportieren.

## Vermietvorgänge (ausgehende Vermietung)

Spiegelbildlich dazu bildet ein **Vermietvorgang** ab, dass man Geräte an einen **Mieter** verleiht. Die vier Status-Schritte:

1. **Gerichtet** — Material ist für die Übergabe vorbereitet
2. **Hinweg: Übergeben** — an den Mieter übergeben
3. **Rückweg: Angenommen** — vom Mieter zurückerhalten
4. **Geprüft** — Rückgabe wurde kontrolliert

Auch hier gibt es Transport-Logistik, Erinnerungen und den **„Materialliste (PDF)"**-Export.

## Erinnerungen per E-Mail

Für Miet- und Vermietvorgänge lässt sich einstellen, wie viele Tage vor Beginn bzw. Ende eine Erinnerungsmail verschickt werden soll — an eine ausgewählte **Mailingliste** (unter **Verwaltung → Mailinglisten** verwaltet) oder die Standardliste. So vergisst man Abhol- oder Rückgabetermine nicht.

## Slack-Anbindung

Miet-/Vermietvorgänge und Produktionen werden zusätzlich live in Slack gespiegelt — in zwei getrennten Kanälen:

- **Kanal für Miet-/Vermietvorgänge** — hier erscheint pro Vorgang eine Nachricht mit Vermieter/Mieter, Zeitraum, Transportart, zugeordneten Geräten, den Produktionen, für die das Material benötigt wird, und einem Link zurück in die App.
- **Kanal für Produktionen** — hier erscheint pro Produktion eine Nachricht mit Zeitraum, Kunde, Ort, einer **Zuordnungs-Ampel** (⚪/🔴/🟢, basierend auf dem VB-Protokoll-Abgleich), dem aktuellen Packstatus („X/Y Geräte gepackt") sowie Links zu VB-Protokoll, Packliste und PDF.

Das Besondere: Es gibt **pro Vorgang immer nur eine einzige, fortlaufend aktualisierte Nachricht** — es wird also nicht bei jeder Änderung neu gepostet, sondern dieselbe Nachricht in Slack live aktualisiert.

Unter jeder Nachricht liegen **Buttons für die jeweils noch offenen Status-Schritte** (z. B. „Geprüft", „Zur Rückgabe fertig", „Gerichtet" oder bei Produktionen „Packvorgang abschließen"). Ein Klick auf einen Button in Slack setzt exakt denselben Haken wie der entsprechende Button in der App — beide Seiten bleiben synchron. Slack ordnet den Klick anhand der E-Mail-Adresse automatisch der passenden Person in der App zu (falls das Konto per gleicher E-Mail existiert), damit im Protokoll nachvollziehbar bleibt, wer bestätigt hat.

Ist ein Vorgang vollständig abgeschlossen, verschwinden die Buttons; die Nachricht wird nach 48 Stunden automatisch zu einer kurzen Ein-Zeilen-Zusammenfassung („✅ … abgeschlossen") zusammengefasst, damit der Kanal übersichtlich bleibt.

## Bestellungen (Einkauf über Slack)

Bestellungen werden direkt in Slack erfasst und von dort bis zur Ablage der Rechnung begleitet. In der App gibt es dazu die Seite **Verwaltung → Einkauf → Bestellungen**.

**So läuft es ab:**

1. **Bestellung anlegen:** In Slack `/bestellung` eintippen. Es öffnet sich ein Formular mit **Bestellt bei**, **Bestellt was** und **Bezahlt mit** (alle drei Pflicht, das Datum setzt die App selbst). Nach dem Absenden erscheint im Bestell-Kanal eine Nachricht je Bestellung. Das gilt für Benutzer und Admins; Betrachter und unbekannte Slack-Konten bekommen eine Ablehnung, die nur sie selbst sehen.
2. **Sendung nachtragen:** Mit dem Knopf **Sendung eintragen** an der Nachricht Versanddienstleister und Sendungsnummer ergänzen. Die Bestellung ist dann **Unterwegs**, und die Nachricht enthält einen Link zur Sendungsverfolgung des Paketdienstes. Die App fragt den Sendungsstatus nicht ab; es gibt nur den Link. Eine eingetragene Sendung lässt sich überschreiben.
3. **Rechnung ablegen:** Die Rechnung als Datei **in den Thread** dieser Nachricht hochladen. Sie wird nicht in der App gespeichert, sondern bleibt in Slack. Danach auf **Rechnung liegt vor** klicken (jeder Benutzer oder Admin darf das, nicht nur der Besteller). Der Bot spricht dann die Buchhaltung im Thread an.
4. **Bezahlt & abgelegt:** Die Buchhaltung lädt die Rechnung aus Slack herunter, archiviert sie selbst und klickt **Bezahlt & abgelegt**. Diesen Knopf dürfen nur Benutzer mit dem Häkchen **Buchhaltung** drücken; den Haken setzt ein Admin unter **Verwaltung → Benutzer**. Danach verschwinden die Knöpfe und die Nachricht zeigt die Zeile „✅ bezahlt & abgelegt von … am …".

**Die vier Stufen:** Bestellt → Unterwegs → Rechnung liegt vor → Bezahlt & abgelegt.

**Erinnerung:** Steht eine Bestellung länger als 30 Tage auf „Rechnung liegt vor", erinnert der Bot die Buchhaltung im Thread, danach alle 7 Tage, bis „Bezahlt & abgelegt" gesetzt ist. Hintergrund: Im kostenlosen Slack-Tarif werden Dateien nach spätestens einem Jahr gelöscht, daher soll die Buchhaltung die Rechnung vorher ablegen.

**Die Seite in der App:** Die Liste zeigt Datum, Besteller, Bestellt bei, Bestellt was, Bezahlt mit, Sendung (Link) und Stufe. Der Filter steht standardmäßig auf **Offen** (alles außer Bezahlt & abgelegt), dazu gibt es **Bezahlt & abgelegt**, **Alle** und eine Suche. Benutzer und Admins können Tippfehler korrigieren, die Sendung nachtragen und Stufen weiterschalten oder zurücknehmen; Betrachter sehen die Liste nur. Jede Änderung aktualisiert die Slack-Nachricht. Ist Slack nicht erreichbar oder kein Kanal eingestellt, wird die Bestellung trotzdem gespeichert.

## Protokoll (Aktivitätslog)

Unter **Verwaltung → Protokoll** (für die Rolle „Benutzer" und „Admin") lässt sich nachvollziehen, wer wann was geändert hat — auch Bestätigungen, die über Slack kamen, tauchen hier auf.

## Einstellungen & Benutzerverwaltung (nur Admin)

Admins verwalten unter **Verwaltung → Benutzer** die Konten und Rollen der Nutzer:innen. Unter **Verwaltung → Einstellungen** lassen sich außerdem:

- die Slack-Anbindung je Bereich (Miet-/Vermietvorgänge bzw. Produktionen) ein-/ausschalten und der jeweilige Ziel-Kanal hinterlegt werden,
- den Slack-Kanal für Bestellungen hinterlegen,
- die Standard-Vorlaufzeit (in Tagen) für Erinnerungsmails vor Beginn/Ende festlegen.

## Typischer Ablauf in Kurzform

1. Produktion anlegen → Packliste erstellen (ggf. Kamerazüge konfigurieren)
2. Bei Bedarf VB-Protokoll ausfüllen (Bedarf festhalten) und daraus die Packliste vorschlagen, prüfen und übernehmen
3. Vor dem Dreh: Packen durchgehen und abschließen
4. Fehlt Equipment, das erst angemietet werden muss: Mietvorgang anlegen, Status-Schritte abhaken
5. Verleiht man selbst Geräte: Vermietvorgang anlegen, Status-Schritte abhaken
6. Timeline und Packen jederzeit zur Kontrolle nutzen
7. Einkäufe: in Slack `/bestellung`, Sendung und Rechnung nachtragen, Buchhaltung legt ab
