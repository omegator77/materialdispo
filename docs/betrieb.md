# Betrieb: Deploy, Scheduler, Slack, Backup

Diese Seite ergänzt die README um alles, was für den Produktionsbetrieb
(Docker-VPS, MySQL) nötig ist.

## Deploy

Auf dem VPS im App-Verzeichnis (Branch `main`):

```bash
git pull
docker exec laravel-app composer install --no-dev --optimize-autoloader
docker exec laravel-app php artisan migrate --force
docker exec laravel-app npm run build
docker exec laravel-app php artisan config:cache
docker exec laravel-app php artisan route:cache
docker exec laravel-app php artisan view:cache
```

Wichtig:

- **`migrate --force` gehört zu jedem Deploy.** Ohne diesen Schritt kommen neue
  Tabellen und Indizes (z. B. Unique-Index auf `item_production`) nie in die
  Produktion.
- **Vor jeder Migration ein Backup ziehen** (siehe unten), besonders bei
  Migrationen, die Spalten entfernen oder Daten umschreiben.
- `config:cache` liest die `.env` einmalig ein. Nach Änderungen an der `.env`
  muss `php artisan config:cache` erneut laufen.
- Die Migration `2026_07_16_100000_migrate_vb_protokoll_freitext_to_bloecke`
  rettet den Freitext der VB-Protokolle, bevor die alten Spalten entfallen. Sie
  muss vor `2026_07_16_100001`/`..100002` laufen (das ist durch die
  Dateinamen gegeben).

## Scheduler (Cron)

Reminder-Mails, Slack-Erinnerungen und das Kompaktieren abgeschlossener
Slack-Nachrichten laufen über den Laravel-Scheduler. **Ohne diesen Cron werden
keine Erinnerungen verschickt.** Auf dem Host:

```cron
* * * * * docker exec laravel-app php artisan schedule:run >> /dev/null 2>&1
```

Der Scheduler startet `reminders:send-transport` stündlich zwischen 07:00 und
19:00 Uhr (Zeitzone der App, siehe `APP_TIMEZONE`) und `slack:compact-completed`
stündlich. Prüfen: `docker exec laravel-app php artisan schedule:list`.

Ein Reminder wird erst reserviert, dann versendet. Scheitern **alle** Mails
einer Erinnerung (z. B. SMTP-Ausfall), wird die Reservierung freigegeben, der
Command endet mit Exit-Code 1 und der nächste Lauf versucht es erneut.

## Queue

Die App schickt Mails und Slack-Nachrichten derzeit synchron; es gibt keine
Queue-Jobs (`ShouldQueue`/`dispatch` kommen im Code nicht vor). Ein
Queue-Worker ist daher nicht nötig. Wenn später Jobs hinzukommen, braucht es
`php artisan queue:work` (z. B. als eigener Container) und
`php artisan queue:restart` bei jedem Deploy.

## Produktions-`.env`

Die mitgelieferte `.env.example` ist für die lokale Entwicklung gedacht. In
Produktion mindestens:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<deine-domain>
APP_TIMEZONE=Europe/Berlin
SESSION_SECURE_COOKIE=true

DB_CONNECTION=mysql
# DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD

MAIL_MAILER=smtp
# MAIL_HOST / MAIL_PORT / MAIL_USERNAME / MAIL_PASSWORD
MAIL_FROM_ADDRESS="dispo@<deine-domain>"

# Nur setzen, wenn dein Reverse-Proxy NICHT in einem privaten Netz hängt
# (Standard: PRIVATE_SUBNETS, siehe .env.example):
# TRUSTED_PROXIES=<proxy-ip>
```

`APP_DEBUG=true` in Produktion gibt Stacktraces, Konfiguration und Zugangsdaten
an Besucher preis.

## Slack-Anbindung

1. Slack-App anlegen, **Bot Token Scopes**: `chat:write`, `users:read`,
   `users:read.email`.
2. **Interactivity** einschalten, Request URL: `https://<deine-domain>/slack/interactions`.
3. In der `.env`: `SLACK_BOT_TOKEN`, `SLACK_SIGNING_SECRET`,
   `SLACK_REMINDER_CHANNEL`, `SLACK_PRODUCTION_CHANNEL` (Kanalnamen **in
   Anführungszeichen**, sonst schneidet dotenv alles ab `#` ab).
4. Den Bot in die Kanäle einladen.

Berechtigungen: Ein Klick auf einen Bestätigungs-Button in Slack wirkt nur, wenn
die Slack-E-Mail-Adresse einem App-Benutzer mit Rolle `admin` oder `user`
gehört. Andere sehen eine Ablehnung, die nur sie selbst sehen.

## Backup

`scripts/backup-db.sh` zieht einen MySQL-Dump aus dem Container
`laravel-mysql`, prüft ihn und legt ihn komprimiert unter `../backups/` ab
(Aufbewahrung 90 Tage). Cron-Eintrag und Hinweise stehen im Kopf des Skripts.

```cron
15 3 * * * /bin/bash /home/noicebard/docker-projects/laravel-app/app/scripts/backup-db.sh >> /home/noicebard/docker-projects/laravel-app/backups/backup.log 2>&1
```

Empfohlen:

- `HEALTHCHECK_URL` setzen (z. B. healthchecks.io): Das Skript pingt die URL nach
  jedem erfolgreichen Backup. Bleibt der Ping aus, kommt ein Alarm, auch wenn
  der Cron still ausfällt.
- Backups zusätzlich **außerhalb des VPS** ablegen (z. B. `rclone`/`rsync` auf
  einen anderen Anbieter). Ein Backup auf derselben Maschine schützt nicht vor
  deren Verlust.
- Hochgeladene Fotos (VB-Protokolle, `storage/app/public`) sind **nicht** im
  Datenbank-Dump und müssen separat gesichert werden.

### Backup einspielen

Erst gegen eine **Kopie** der Datenbank testen, nicht gegen die echte.

```bash
gunzip -c ../backups/laravel_<zeitstempel>.sql.gz \
  | docker exec -i laravel-mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysql -u laravel laravel'
```

Danach `php artisan migrate --force` (falls der Dump älter als der Code ist) und
`php artisan config:cache`.

Mindestens einmal pro Quartal einen Restore in einer Testumgebung durchspielen:
Ein Backup, das nie eingespielt wurde, ist nicht bewiesen.

## Tests und CI

`vendor/bin/pest` (SQLite im Speicher, keine `.env` nötig; die Vite-Assets
müssen gebaut sein: `npm run build`). Der CI-Workflow `.github/workflows/ci.yml`
führt Pint, die Tests, einen MySQL-Lauf (noch informativ) und
`composer audit`/`npm audit` aus.
