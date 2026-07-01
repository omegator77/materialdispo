# Material Dispo

Verwaltungssystem für Produktionsausstattung in der Film- und Videoproduktion. Trackt Mietequipment (Kameras, Objektive, Stative, Monitore), ordnet es Produktionen zu und erstellt Packlisten und Kamerakonfigurationen.

## Features

- **Geräteverwaltung** — Inventar mit Kategorien, Mietdaten und Lieferanten; polymorphe Detailtypen für Kameras, Monitore und Objektive
- **Produktionsplanung** — Produktionen anlegen, Geräte zuweisen mit Verfügbarkeitsprüfung (Mietfenster + Überschneidungen)
- **Kamerakonfigurationen** — Kamerazüge pro Produktion mit Objektiv, Stativ, Stativkopf und Adapter
- **Packliste** — Gesamtübersicht aller Produktionen mit Kamerazügen und Einzelgeräten, filterbar, PDF-Export
- **Geräte-Timeline** — Gantt-Ansicht aller Geräte über einen Zeitraum, zoom- und scrollbar
- **Dashboard** — Überblick über aktuelle Belegung

## Tech Stack

| Bereich | Technologie |
|---|---|
| Backend | Laravel 11 (PHP 8.2+) |
| Datenbank | SQLite (lokal) / MySQL (Produktion) |
| Frontend | Blade, Alpine.js, Tailwind CSS |
| Build | Vite 5 |
| PDF | barryvdh/laravel-dompdf |
| Auth | Laravel Breeze |

## Lokale Entwicklung

Voraussetzungen: [Laravel Herd](https://herd.laravel.com), Node.js

```bash
git clone git@github.com:omegator77/materialdispo.git
cd materialdispo
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
```

Die App läuft dann unter `http://materialdispo.test` (Herd).

## Branching & Deploy

```
feature/fix → develop → main → VPS
```

1. Entwicklung auf `develop`
2. Merge nach `main` wenn stabil
3. Auf dem VPS pullen und Assets bauen:

```bash
git pull
docker exec -it laravel-app npm run build
```

## Projektstruktur

```
app/
  Http/Controllers/   — dünne Controller (HTTP-Orchestrierung)
  Http/Requests/       — Form Requests (Validierung pro Ressource)
  Models/             — Eloquent Models
  Services/           — Business-Logik (Verfügbarkeitsprüfung, Kamerakonfiguration,
                         Vorlagen-Import, Geräte-Detail-Sync, VB-Protokoll-Anforderungen)
resources/
  views/              — Blade Templates
    productions/      — Produktionsverwaltung
    items/            — Geräteverwaltung
    timeline/         — Geräte-Timeline
    itemproductions/  — Packliste
    camera_configs/   — Kamerakonfigurationen
    pdf/              — PDF-Templates
```
