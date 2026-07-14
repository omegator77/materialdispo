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
| **Betrachter** | Nur lesen: Dashboard, Geräte, Produktionen, Timeline, Packliste, PDFs ansehen |
| **Benutzer** | Wie Betrachter, plus: Geräte/Produktionen/Vorgänge anlegen, bearbeiten, Haken setzen (z. B. „Packvorgang abschließen") |
| **Admin** | Wie Benutzer, plus: Benutzerverwaltung und Einstellungen |

Falls ein Button fehlt, den man erwarten würde — meistens liegt es an der Rolle, nicht an einem Fehler.

## Die Hauptnavigation

Oben in der Leiste:

- **Dashboard** — Übersicht über aktuelle Belegung und offene Vorgänge
- **Geräte** — das komplette Inventar
- **Produktionen** — laufende und geplante Drehs/Projekte
- **Packliste** — Gesamtübersicht aller Produktionen mit ihrem Material
- **Timeline** — Zeitstrahl, wer welches Gerät wann belegt
- **Verwaltung** (Dropdown) — Stammdaten (Gruppen, Gerätetypen, Mailinglisten), Miet- und Verleihvorgänge, sowie (für Admins) Benutzer und Einstellungen

## Dashboard: der Einstieg

Nach dem Login landet man auf dem Dashboard. Es zeigt auf einen Blick:

- **Kennzahlen** oben: „Aktive Vorgänge" (heute laufende Produktionen/Miet-/Vermietvorgänge), „Geräte gesamt" (inkl. Anteil Mietmaterial), „Heute gebucht" sowie die letzten Aktionen aus dem Protokoll.
- **Laufende Vorgänge** — was gerade heute läuft.
- **Nächste Vorgänge** — was als Nächstes ansteht.
- **Offene Vorgänge** — Miet-/Vermietvorgänge und Produktionen, bei denen noch nicht alle Status-Haken gesetzt sind, mit direktem Link zum jeweiligen Vorgang.
- **Zuletzt angelegte Produktionen** als Tabelle mit Start-/Enddatum und Link zum Öffnen.

## Geräte, Gerätetypen und Gruppen

Jedes Gerät (z. B. „LDX 86N WorldCam", Nr. 22) gehört zu:

- einer **Gruppe** (z. B. „Kameras", „Optiken", „Stativ", „Audio", „Intercom") — dient zur Sortierung und zum Filtern in Timeline und Packliste,
- einem **Gerätetyp** (z. B. „LDX 86N WorldCam" bei Kameras oder „Canon 22 Fach" bei Objektiven) — wird u. a. für Materialanforderungen im VB-Protokoll genutzt.

Auf der Geräte-Detailseite sieht man Stammdaten, ob es sich um **Eigenmaterial** oder **Mietmaterial** (inkl. Vermieter, z. B. „Pixmade") handelt, und in welchen Produktionen bzw. Miet-/Vermietvorgängen das Gerät aktuell steckt. Kameras, Monitore und Objektive haben zusätzliche technische Detailfelder.

Neue Geräte, Gerätetypen und Gruppen legt man über **Verwaltung** an bzw. über die „+"/„Neu"-Buttons auf den jeweiligen Listenseiten an.

## Produktionen: Material zuordnen

Eine **Produktion** ist ein Dreh/Projekt mit Bezeichnung sowie Start- und Enddatum. Auf der Detailseite kann man:

1. **Material hinzufügen** — über die Suche einzelne Geräte zuordnen. Bei Kameras kann man statt „Standard hinzufügen" auch **„Konfigurieren"** wählen, um einen kompletten Kamerazug (Kamera + Objektiv + Stativ + Kopf + Adapter) zusammenzustellen.
2. Verknüpfte Miet-/Vermietvorgänge einsehen (Link „Bearbeiten →").
3. Ein **VB-Protokoll** (Vorbesprechungsprotokoll) anlegen — dort trägt man Kunde, Ort, Zeitplan, Crew und die benötigten Geräte/Mengen ein. Die App vergleicht automatisch Bedarf mit tatsächlich zugeordnetem/gepacktem Material und zeigt eine rot/gelb/grün-Ampel.
4. Die Materialliste als **PDF exportieren**.

## Packvorgang: Verladen abhaken

Der **Packvorgang** ist die praktische Checkliste beim Einladen des Materials für eine Produktion (erreichbar über den Button auf der Packliste oder Produktionsseite):

- Jedes benötigte Gerät (gruppiert nach Kamerazug bzw. Gerätegruppe) hat eine Checkbox. Ein Klick markiert es als gepackt (mit Name und Uhrzeit).
- Ein Fortschrittsbalken zeigt „X von Y Geräten im Rüstwagen".
- Sind alle Geräte gepackt, kann man mit **„Packvorgang abschließen"** den Vorgang sperren. Fehlt noch etwas, muss man das bestätigen („Trotz fehlender Geräte abschließen").
- Ein abgeschlossener Packvorgang lässt sich über **„Wieder öffnen"** erneut bearbeiten.
- Über **„PDF-Checkliste"** lässt sich die Liste ausdrucken.

## Packliste: der Überblick über alles

Die **Packliste** (im Hauptmenü) zeigt alle Produktionen als Karten — laufend, kommend, vergangen oder archiviert — jeweils mit ihren Kamerazügen und weiteren Geräten. Von hier aus gelangt man direkt zum Packvorgang (mit Fortschrittsanzeige), zum PDF-Export und — sobald der Packvorgang abgeschlossen ist — zum **Abgleich-Report** (Soll aus dem VB-Protokoll vs. tatsächlich gepacktes Material).

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

## Protokoll (Aktivitätslog)

Unter **Verwaltung → Protokoll** (für die Rolle „Benutzer" und „Admin") lässt sich nachvollziehen, wer wann was geändert hat — auch Bestätigungen, die über Slack kamen, tauchen hier auf.

## Einstellungen & Benutzerverwaltung (nur Admin)

Admins verwalten unter **Verwaltung → Benutzer** die Konten und Rollen der Nutzer:innen. Unter **Verwaltung → Einstellungen** lassen sich außerdem:

- die Slack-Anbindung je Bereich (Miet-/Vermietvorgänge bzw. Produktionen) ein-/ausschalten und der jeweilige Ziel-Kanal hinterlegt werden,
- die Standard-Vorlaufzeit (in Tagen) für Erinnerungsmails vor Beginn/Ende festlegen.

## Typischer Ablauf in Kurzform

1. Produktion anlegen → Material zuordnen (ggf. Kamerazüge konfigurieren)
2. Bei Bedarf VB-Protokoll ausfüllen (Bedarf festhalten)
3. Vor dem Dreh: Packvorgang durchgehen und abschließen
4. Fehlt Equipment, das erst angemietet werden muss: Mietvorgang anlegen, Status-Schritte abhaken
5. Verleiht man selbst Geräte: Vermietvorgang anlegen, Status-Schritte abhaken
6. Timeline und Packliste jederzeit zur Kontrolle nutzen
