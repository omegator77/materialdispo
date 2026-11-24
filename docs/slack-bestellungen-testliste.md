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
