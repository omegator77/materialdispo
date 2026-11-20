<?php

namespace App\Support;

use Illuminate\Support\Str;

class PdfFilename
{
    /**
     * Baut einen sicheren Download-Dateinamen aus frei eingegebenen Bezeichnungen:
     * nur ASCII-Buchstaben, Ziffern, Leerzeichen, Punkt, Unter- und Bindestrich;
     * keine Pfadtrenner, keine ".."-Folgen, Länge begrenzt.
     */
    public static function make(string $prefix, ?string $name = null, string $suffix = ''): string
    {
        $base = trim($prefix.' '.($name ?? ''));
        $base = Str::ascii($base);
        $base = preg_replace('/[^A-Za-z0-9 _.\-]+/', '_', $base);
        $base = preg_replace('/\.{2,}/', '.', $base);
        $base = trim(Str::limit($base, 100, ''), ' ._-');

        return ($base === '' ? 'Dokument' : $base).$suffix.'.pdf';
    }
}
