<?php

namespace App\Support;

class AppVersion
{
    public static function label(): string
    {
        // Cache-Key an die mtime der HEAD-Referenz koppeln, damit ein Deploy
        // (git pull aktualisiert die Ref-Datei) den Cache sofort invalidiert,
        // statt bis zu 1h auf die alte Version warten zu müssen.
        $cacheKey = 'app_version_label_' . self::fingerprint();

        return cache()->remember($cacheKey, 3600, function () {
            return self::fromGitCli() ?? self::fromGitFiles() ?? 'unbekannt';
        });
    }

    private static function fingerprint(): string
    {
        $headFile = base_path('.git/HEAD');

        if (! is_file($headFile)) {
            return 'no-git';
        }

        $head = trim((string) file_get_contents($headFile));
        $refFile = str_starts_with($head, 'ref:')
            ? base_path('.git/' . trim(substr($head, 4)))
            : null;

        $statFile = ($refFile && is_file($refFile)) ? $refFile : $headFile;

        return (string) filemtime($statFile);
    }

    /**
     * Bevorzugter Weg: git-Kommando direkt aufrufen. Funktioniert nicht,
     * wenn shell_exec deaktiviert ist oder git im Container fehlt/PATH-Probleme hat.
     */
    private static function fromGitCli(): ?string
    {
        if (! function_exists('shell_exec')) {
            return null;
        }

        $hash = trim((string) @shell_exec('git -C ' . escapeshellarg(base_path()) . ' rev-parse --short HEAD 2>&1'));

        if (! $hash || strlen($hash) > 12 || ! ctype_xdigit($hash)) {
            return null;
        }

        $date = trim((string) @shell_exec('git -C ' . escapeshellarg(base_path()) . ' log -1 --format=%cd --date=format:"%d.%m.%Y %H:%M" 2>&1'));

        return $date ? "{$hash} · {$date}" : $hash;
    }

    /**
     * Fallback: .git-Verzeichnis direkt auslesen, ohne das git-Binary
     * aufzurufen. Funktioniert auch wenn shell_exec gesperrt ist oder
     * git im Container Ownership-Probleme macht (Docker-Volumes).
     */
    private static function fromGitFiles(): ?string
    {
        $gitDir = base_path('.git');
        $headFile = $gitDir . '/HEAD';

        if (! is_file($headFile)) {
            return null;
        }

        $head = trim((string) file_get_contents($headFile));
        $refFile = null;
        $hash = null;

        if (str_starts_with($head, 'ref:')) {
            $ref = trim(substr($head, 4));
            $refFile = $gitDir . '/' . $ref;

            if (is_file($refFile)) {
                $hash = trim((string) file_get_contents($refFile));
            } else {
                $packedRefs = $gitDir . '/packed-refs';
                if (is_file($packedRefs)) {
                    foreach (file($packedRefs) as $line) {
                        $line = trim($line);
                        if (str_ends_with($line, " {$ref}")) {
                            $hash = trim(explode(' ', $line)[0]);
                            break;
                        }
                    }
                }
            }
        } else {
            $hash = $head;
        }

        if (empty($hash)) {
            return null;
        }

        $short = substr($hash, 0, 7);
        $mtime = $refFile && is_file($refFile) ? filemtime($refFile) : filemtime($headFile);
        $date = $mtime ? date('d.m.Y H:i', $mtime) : null;

        return $date ? "{$short} · {$date}" : $short;
    }
}
