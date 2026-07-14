<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

class DocumentationController extends Controller
{
    public function show()
    {
        $markdown = file_get_contents(base_path('docs/anleitung.md'));

        return view('documentation.show', [
            'html' => Str::markdown($markdown),
        ]);
    }
}
