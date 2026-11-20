<?php

namespace App\Http\Controllers;

use App\Http\Requests\VbVorschlagApplyRequest;
use App\Models\Production;
use App\Services\VbVorschlagService;

class VbVorschlagController extends Controller
{
    public function __construct(private VbVorschlagService $service) {}

    public function show(Production $production)
    {
        return view('vb-vorschlag.show', [
            'production' => $production,
            'proposal' => $this->service->propose($production),
        ]);
    }

    public function store(VbVorschlagApplyRequest $request, Production $production)
    {
        $data = $request->validated();
        $result = $this->service->apply($production, [
            'rigs' => $data['rigs'] ?? [],
            'items' => $data['items'] ?? [],
        ]);

        $parts = [];
        if ($result['created']) {
            $parts[] = count($result['created']).' Kamerazug/-züge angelegt';
        }
        if ($result['attached']) {
            $parts[] = count($result['attached']).' Gerät(e) zur Packliste hinzugefügt';
        }

        $redirect = redirect()->route('productions.show', $production)
            ->with('success', $parts ? implode(', ', $parts).'.' : 'Es wurde nichts übernommen.');

        $warnings = array_merge($result['warnings'], $result['skipped'] ? ['Übersprungen: '.implode('; ', $result['skipped'])] : []);
        if ($warnings) {
            $redirect->with('warning', implode(' ', $warnings));
        }

        return $redirect;
    }
}
