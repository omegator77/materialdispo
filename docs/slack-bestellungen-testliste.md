# Testliste: Bestellungen in Slack (manuell)

Voraussetzung: Slack-App eingerichtet laut [betrieb.md](betrieb.md) (Scope `commands`, Befehl
`/bestellung` mit `https://<domain>/slack/commands`, Bot im Bestell-Kanal, Kanal in
Verwaltung → Einstellungen oder `SLACK_ORDER_CHANNEL`), Migrationen gelaufen. Zum Testen je ein
Konto als Benutzer, als Betrachter und als Buchhaltung (Häkchen in der Benutzerverwaltung)
bereithalten; die E-Mail-Adresse muss in Slack und App gleich sein.

1. **Befehl absetzen** (als Benutzer): `/bestellung` in einem beliebigen Kanal eintippen.
   Erwartet: Formular öffnet sich sofort.
2. **Formular**: Absenden ohne eines der drei Felder wird abgelehnt; mit Bestellt bei, Bestellt was,
   Bezahlt mit absenden. Erwartet: Nachricht im Bestell-Kanal, Stufe "Bestellt", Datum und Besteller
   stimmen; in der App unter Verwaltung → Einkauf → Bestellungen steht die Bestellung (Filter Offen).
3. **Sendung nachtragen**: Knopf "Sendung eintragen", Dienstleister und Nummer eingeben.
   Erwartet: dieselbe Nachricht wird aktualisiert, Stufe "Unterwegs", Link zur Sendungsverfolgung
   funktioniert. Danach die Sendung nochmals mit anderer Nummer überschreiben.
4. **Rechnung hochladen**: Eine Datei in den Thread der Nachricht hochladen, dann "Rechnung liegt vor"
   klicken (auch als anderer Benutzer als der Besteller testen). Erwartet: Stufe "Rechnung liegt vor",
   der Bot spricht die Buchhaltung im Thread an.
5. **Bezahlt & abgelegt**: Als Benutzer ohne Häkchen klicken: Ablehnung (nur für dich sichtbar).
   Als Buchhaltung klicken: die Knöpfe verschwinden und die Zeile "✅ bezahlt & abgelegt von … am …" erscheint; in der App Filter
   "Bezahlt & abgelegt". In der App "Zurücknehmen" testen.
6. **Erinnerung**: Eine Bestellung auf "Rechnung liegt vor" bringen und in der Datenbank
   `rechnung_vorhanden_at` auf vor mehr als 30 Tagen setzen (z. B.
   `php artisan tinker`, `Bestellung::first()->update(['rechnung_vorhanden_at' => now()->subDays(31)])`).
   Dann `php artisan bestellungen:erinnern` ausführen. Erwartet: Erinnerung im Thread an die
   Buchhaltung. Sofort nochmals ausführen: keine zweite Erinnerung (erst nach 7 Tagen wieder).
7. **Kein Kanal**: Kanal in den Einstellungen leeren (und `SLACK_ORDER_CHANNEL` leer), `/bestellung`
   absenden. Erwartet: Bestellung wird in der App gespeichert, es gibt einen Hinweis statt
   einer Nachricht, kein Fehler. Kanal danach wieder eintragen; in der App "neu senden" prüfen.
8. **Betrachter**: `/bestellung` als Betrachter oder mit unbekannter E-Mail-Adresse. Erwartet:
   Ablehnung (nur für dich sichtbar), keine Bestellung. In der App sieht der Betrachter die Liste,
   aber keine Bearbeiten-Knöpfe.
9. **Doppelklick**: Formular schnell zweimal absenden bzw. Knopf doppelt klicken. Erwartet: nur
   eine Bestellung, keine doppelte Erinnerung.
10. **Sendung angekommen mit Notiz**: Auf einer Bestellung (egal welche Stufe außer Bezahlt & abgelegt) den Knopf
    "Sendung angekommen" klicken, Notiz "Lager B, Regal 3" eingeben, speichern. Erwartet: Knopf
    verschwindet, die Nachricht zeigt "📦 Angekommen am … Uhr von Name — Lager B, Regal 3"; Stufe und
    übrige Knöpfe unverändert. In der App zeigt die Detailseite die Ankunft, die Liste das Kennzeichen "angekommen".
11. **Sendung angekommen ohne Notiz**: Bei einer zweiten Bestellung das Formular leer absenden.
    Erwartet: Zeile "📦 Angekommen am … Uhr von Name" ohne " — …", kein Fehler.
12. **Zweiter Klick**: Auf einer bereits angekommenen Bestellung einen veralteten Knopf (zweiter Slack-Tab
    oder Formular doppelt absenden) auslösen. Erwartet: Zeit, Name und Notiz der ersten Ankunft bleiben unverändert.
13. **Betrachter**: "Sendung angekommen" als Betrachter oder mit unbekannter E-Mail-Adresse klicken. Erwartet:
    Ablehnung (nur für dich sichtbar), keine Änderung an der Nachricht.
14. **Zurücknehmen in der App**: Detailseite der angekommenen Bestellung, "Ankunft zurücknehmen" (Rückfrage
    bestätigen). Erwartet: die 📦-Zeile verschwindet aus der Slack-Nachricht, der Knopf "Sendung angekommen"
    ist wieder da. Zusätzlich in der App die Notiz einer angekommenen Bestellung unter Bearbeiten ändern;
    die Slack-Zeile zeigt den neuen Text.
15. **Zeile bleibt nach Bezahlt & abgelegt**: Eine angekommene Bestellung über "Rechnung liegt vor" bis
    "Bezahlt & abgelegt" bringen (als Buchhaltung). Erwartet: Knöpfe weg, die 📦-Zeile steht weiterhin über der
    Zeile "✅ bezahlt & abgelegt …".
