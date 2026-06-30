<?php

namespace App\Support;

class AppVersion
{
    public static function label(): string
    {
        return cache()->remember('app_version_label', 3600, function () {
            $hash = trim((string) @shell_exec('git rev-parse --short HEAD 2>&1'));
            $date = trim((string) @shell_exec('git log -1 --format=%cd --date=format:"%d.%m.%Y %H:%M" 2>&1'));

            if (! $hash || str_contains($hash, 'fatal') || strlen($hash) > 12) {
                return 'unbekannt';
            }

            return $date ? "{$hash} · {$date}" : $hash;
        });
    }
}
