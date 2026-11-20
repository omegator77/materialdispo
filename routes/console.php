<?php

use Illuminate\Support\Facades\Schedule;

// Erinnerungen sind tagesgenau fällig: stündlich genügt (und wiederholt
// fehlgeschlagene Versuche), nachts (App-Zeitzone) wird nichts verschickt.
Schedule::command('reminders:send-transport')->hourly()->between('07:00', '19:00')->withoutOverlapping();
Schedule::command('slack:compact-completed')->hourly()->withoutOverlapping();
