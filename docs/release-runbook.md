# Release-Runbook (Produktion, Docker-VPS)

Schrittweise Anleitung für einen sorgfältigen Deploy. **Jede Phase endet mit einem
Haltepunkt**: erst weiter, wenn die Prüfung stimmt. Pfade und Containernamen sind die
des VPS (`/home/noicebard/docker-projects/laravel-app`, Container `laravel-app`,
`laravel-nginx`, `laravel-mysql`); Hintergründe stehen in [betrieb.md](betrieb.md).

Alle Befehle im Projektordner ausführen:

```bash
cd /home/noicebard/docker-projects/laravel-app
```

Dieses Release enthält **Datenbank-Migrationen**. Sie wurden auf MySQL 8.0 mit Beispieldaten
(Freitext in VB-Protokollen, doppelte Geräte-Zuordnungen, Umlaute und Sonderzeichen)
durchgespielt, einschließlich Rückweg und erneutem Ausrollen. Produktion läuft auf MySQL 8.4;
das ist nicht identisch, daher bleibt Phase B Pflicht. Drei Migrationen sind neu:

| Migration | Wirkung | Rückgängig |
|---|---|---|
| `2026_07_16_100000_migrate_vb_protokoll_freitext_to_bloecke` | kopiert den Freitext der VB-Protokolle in die neuen Blöcke (nur, wenn die Altspalten noch existieren) | nicht nötig (Daten bleiben erhalten) |
| `2026_07_17_100000_add_unique_index_to_item_production_table` | **löscht doppelte** Geräte-Zuordnungen je Produktion, setzt dann einen Unique-Index | `migrate:rollback` entfernt den Index; gelöschte Duplikate nur per Backup |
| `2026_07_18_100000_add_booking_period_index_to_productions_table` | Index für die Verfügbarkeitsprüfung | `migrate:rollback` |

### Zusatz für Bestellungen (zwei weitere neue Migrationen)

| Migration | Wirkung | Rückgängig |
|---|---|---|
| `2026_11_24_100000_create_bestellungen_table` | neue Tabelle `bestellungen` | `migrate:rollback` (Bestelldaten gehen dabei verloren) |
| `2026_11_24_100100_add_ist_buchhaltung_to_users_table` | neue Spalte `users.ist_buchhaltung` (Standard false) | `migrate:rollback` |

Beide legen nur Neues an und verändern keine vorhandenen Daten. Phase B (Datenbank-Backup) gilt
trotzdem. Slack-Einrichtung für `/bestellung` (Scope `commands`, Befehl, Kanal): siehe
[betrieb.md](betrieb.md); nach dem Deploy Haken **Buchhaltung** in der Benutzerverwaltung setzen.

### Zusatz für "Sendung angekommen" (eine weitere neue Migration)

| Migration | Wirkung | Rückgängig |
|---|---|---|
| `2026_11_26_100000_add_angekommen_to_bestellungen_table` | neue Spalten `bestellungen.angekommen_at`, `angekommen_by`, `angekommen_notiz` (alle leer) | `migrate:rollback` (eingetragene Ankünfte gehen dabei verloren) |

Sie legt nur Neues an und verändert keine vorhandenen Daten. **Datenbank vorher sichern** (Phase B).
Es gibt **keine neuen Slack-Scopes** und keine Änderung an der Slack-Einrichtung; die Nachrichten
bestehender Bestellungen erhalten den Knopf "Sendung angekommen" beim nächsten Aktualisieren.

---

## Phase A: Vorprüfung (nur lesen, nichts wird verändert)

```bash
# A1: Ist der Server-Checkout sauber? (lokale Änderungen würden den Deploy stören)
git -C app status --short
git -C app branch --show-current
git -C app log --oneline -1

# A2: Welche Migrationen stehen aus? Wurde der Freitext schon gedroppt?
docker exec laravel-app php artisan migrate:status | grep -v "Ran" 
docker exec laravel-app php artisan migrate:status | grep -E "vb_protokoll|freitext"

# A3: Welche Werkzeuge gibt es im App-Container?
docker exec laravel-app sh -c 'php -v | head -1; which composer node npm'

# A4: Genug Platz für Backups? Wie groß sind die Fotos?
df -h /home
du -sh app/storage/app/public

# A5: Läuft die App im Produktionsmodus?
docker exec laravel-app php artisan about --only=environment

# A6: Cron-Stand (Backup und Scheduler)
crontab -l | grep -E "schedule:run|backup-db"

# A7: Welche doppelten Geräte-Zuordnungen löscht die Unique-Migration?
# (leer = nichts wird gelöscht; sonst Liste notieren, der älteste Eintrag je Paar bleibt)
docker exec laravel-mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql -u laravel laravel -e "SELECT production_id, item_id, COUNT(*) AS anzahl FROM item_production GROUP BY production_id, item_id HAVING anzahl > 1;"'
```

**Haltepunkt:** Ausgaben prüfen.
- A1 muss leer sein (keine lokalen Änderungen) oder bekannt.
- **A2 ist entscheidend:** Steht `drop_freitext…` schon als `Ran`, ist der alte Freitext
  der VB-Protokolle in dieser Datenbank bereits gelöscht (nur aus einem Backup holbar),
  und die neue Backfill-Migration läuft als No-op durch. Steht er unter den ausstehenden,
  ist alles gut: der Backfill läuft davor.
- A5: `Environment production`, `Debug mode OFF`.
- A7: Jede Zeile ist ein Gerät, das mehrfach derselben Produktion zugeordnet war; die
  überzähligen Zuordnungen werden gelöscht (fachlich bedeutungslos, aber die Notizen der
  gelöschten Zeilen gehen verloren).

---

## Phase B: Sicherung (vor jeder Änderung)

```bash
mkdir -p ~/pre-deploy && chmod 700 ~/pre-deploy

# B1: Datenbank (vorhandenes Skript) und Integrität prüfen
bash app/scripts/backup-db.sh
ls -lh backups | tail -3
gunzip -t "$(ls -t backups/laravel_*.sql.gz | head -1)" && echo "Dump ist gültig"
cp "$(ls -t backups/laravel_*.sql.gz | head -1)" ~/pre-deploy/

# B2: Hochgeladene Fotos (stehen NICHT im Datenbank-Dump)
tar czf ~/pre-deploy/storage-public.tgz -C app/storage/app public

# B3: Konfiguration und aktueller Code-Stand (für den Rückweg)
cp app/.env ~/pre-deploy/env.bak && chmod 600 ~/pre-deploy/env.bak
git -C app rev-parse HEAD | tee ~/pre-deploy/commit-vorher.txt
```

**Haltepunkt:** Der Dump ist gültig und nicht winzig, die Fotos sind gesichert, der
bisherige Commit ist notiert. **Zusätzlich eine Kopie von `~/pre-deploy` außerhalb des VPS
ablegen** (z. B. `scp` auf deinen Rechner); ein Backup nur auf derselben Maschine
schützt nicht vor deren Verlust.

---

## Phase C: Deploy

Die Seite ist während der Migration kurz im Wartungsmodus. Zeitpunkt wählen, an dem
niemand mitten in einem Einsatz Daten erfasst.

```bash
# C1: Wartungsmodus
docker exec laravel-app php artisan down --retry=60

# C2: Neuen Code holen. Variante "main" (nach Merge):
git -C app fetch origin
git -C app checkout main
git -C app pull --ff-only
# Variante "Branch" (vor dem Merge testen): statt der drei Zeilen oben
#   git -C app fetch origin claude/optimistic-dirac-0n1u74
#   git -C app checkout claude/optimistic-dirac-0n1u74

# C3: PHP-Abhängigkeiten (das Lockfile enthält Sicherheits-Updates)
docker exec laravel-app composer install --no-dev --optimize-autoloader --no-interaction

# C4: Migrationen erst als Trockenlauf ansehen, dann ausführen
docker exec laravel-app php artisan migrate --pretend
docker exec laravel-app php artisan migrate --force

# C5: Assets bauen. Im App-Container gibt es kein node/npm, daher ein Einmal-Container.
# Bewusst als root (ohne -u): public/build gehört root (frühere Builds liefen im Container);
# mit der eigenen Benutzer-ID scheitert Vite beim Leeren des Ordners (EACCES) und baut NICHTS.
docker run --rm -e HOME=/tmp -v "$PWD/app":/app -w /app node:22 sh -c "npm ci && npm run build"
ls -la app/public/build/manifest.json     # muss jetzt ein aktuelles Datum haben (sonst ist der Build gescheitert!)

# C6: Caches neu aufbauen
docker exec laravel-app php artisan config:cache
docker exec laravel-app php artisan route:cache
docker exec laravel-app php artisan view:cache

# C7: PHP-FPM neu starten (verwirft Opcache) und Wartungsmodus beenden
docker restart laravel-app
docker exec laravel-app php artisan up
```

**Haltepunkte:**
- Nach C4 `--pretend`: Es dürfen nur die drei Migrationen oben (plus bekannte
  ausstehende aus A2) auftauchen. Bei Unerwartetem **abbrechen** und `php artisan up`.
- Bricht `migrate --force` mit einem Fehler ab: **nicht** erneut starten, sondern
  Fehlermeldung sichern, `up` ausführen und zurückrollen (Phase E).
- Im App-Container ist `composer` vorhanden, `node`/`npm` nicht. Deshalb baut C5 die
  Assets in einem Einmal-Container (`node:22`); das Image wird beim ersten Mal geladen.

Danach, falls noch nicht eingerichtet, den Scheduler-Cron eintragen (ohne ihn gehen keine
Erinnerungen raus; Details in [betrieb.md](betrieb.md)):

```cron
* * * * * docker exec laravel-app php artisan schedule:run >> /dev/null 2>&1
```

---

## Phase D: Prüfung nach dem Deploy

```bash
# D1: Erreichbarkeit und Fehler im Log
curl -s -o /dev/null -w '%{http_code}\n' https://material.noicebard.de/login      # 200
docker exec laravel-app sh -c 'tail -n 30 storage/logs/laravel.log'                # keine neuen ERROR-Zeilen
docker exec laravel-app php artisan schedule:list                                  # Reminder + Slack-Compact

# D2: Migrationsstand
docker exec laravel-app php artisan migrate:status | grep -vE "Ran"                # leer = nichts ausstehend
```

Im Browser (Admin-Konto) durchklicken:

1. **Login** funktioniert, Dashboard lädt.
2. **VB-Protokoll** einer älteren Produktion öffnen: Die früheren Freitext-Felder müssen
   als Blöcke mit Überschrift (z. B. „Besonderheiten“, „Zeitplan“) vorhanden sein.
3. **Foto-Upload** im VB-Protokoll (3 bis 5 MB) speichern.
4. **PDF-Export** einer Produktion und des VB-Protokolls (mit Foto) erzeugen.
5. **Mietgerät** (mit Vermieter, ohne Mietvorgang) einer Produktion zuordnen: gelbe Warnung
   „noch nicht gemietet“, danach den Mietvorgang an das Gerät hängen.
6. **Packliste** mit einem Betrachter-Konto: nur Lesezugriff, kein Abhaken.
7. **Slack** (falls aktiv): Button-Klick eines Benutzers, dessen Slack-E-Mail mit der
   App-E-Mail übereinstimmt, bestätigt; ein Fremder bekommt „Keine Berechtigung“.

**Achtung Erinnerungen:** Nach dem Deploy kann der Scheduler zu Beginn des nächsten
Zeitfensters (07 bis 19 Uhr) **echte Erinnerungs-Mails** verschicken, wenn Termine
fällig sind. Das ist beabsichtigt. `reminders:send-transport` nicht von Hand
ausführen, außer du willst genau das.

---

## Phase E: Rückweg

**Code zurück** (Datenbank bleibt, die neuen Migrationen sind abwärtskompatibel):

```bash
docker exec laravel-app php artisan down --retry=60
git -C app checkout "$(cat ~/pre-deploy/commit-vorher.txt)"
docker exec laravel-app composer install --no-dev --optimize-autoloader --no-interaction
docker exec laravel-app php artisan config:cache
docker exec laravel-app php artisan route:cache
docker exec laravel-app php artisan view:cache
docker restart laravel-app
docker exec laravel-app php artisan up
```

**Nur die Indizes zurücknehmen** (falls sie Probleme machen):
`docker exec laravel-app php artisan migrate:rollback --step=2`
(entfernt in umgekehrter Reihenfolge den Produktionsindex und den Unique-Index).

**Datenbank komplett zurück** (nur wenn Daten beschädigt wurden; verwirft alles seit
dem Backup):

```bash
gunzip -c ~/pre-deploy/laravel_<zeitstempel>.sql.gz \
  | docker exec -i laravel-mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysql -u laravel laravel'
```

Fotos zurück: `tar xzf ~/pre-deploy/storage-public.tgz -C app/storage/app`.
