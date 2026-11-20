@isset($selectedProduction)
    @if ($selectedProduction)
        <div class="page">
            <a href="{{ route('productions.index') }}"
               class="btn btn-secondary">
                Alle Produktionen anzeigen
            </a>
        </div>
    @endif
@endisset

<div class="page">

    {{-- Desktop / Tablet --}}
    <div class="hidden md:block card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Produktion</th>
                    <th>Beginn</th>
                    <th>Ende</th>
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>

            <tbody>
                @forelse($productions as $production)
                    @php
                        $vbProtokollRoute = $production->vbProtokoll
                            ? route('vb-protokoll.show', $production->id)
                            : route('vb-protokoll.create', $production->id);
                    @endphp
                    <tr>
                        <td class="font-medium">
                            <a href="{{ $vbProtokollRoute }}"
                               class="text-gray-900 hover:text-orange-500">
                                {{ $production->bezeichnung }}
                            </a>
                        </td>

                        <td>
                            {{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '—' }}
                        </td>

                        <td>
                            {{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '—' }}
                        </td>

                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ $vbProtokollRoute }}"
                                   class="btn btn-secondary btn-sm">
                                    VB-Protokoll
                                </a>

                                @if(Auth::user()->isUser())
                                <a href="{{ route('productions.show', $production->id) }}"
                                   class="btn btn-primary btn-sm">
                                    Packliste
                                </a>

                                <a href="{{ route('productions.edit', $production->id) }}"
                                   class="btn btn-secondary btn-sm">
                                    Bearbeiten
                                </a>

                                <a href="{{ route('productions.create', ['from' => $production->id]) }}"
                                   class="btn btn-secondary btn-sm">
                                    Duplizieren
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">
                            Keine Produktionen gefunden.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Handy --}}
    <div class="md:hidden space-y-3">
        @forelse($productions as $production)
            @php
                $vbProtokollRoute = $production->vbProtokoll
                    ? route('vb-protokoll.show', $production->id)
                    : route('vb-protokoll.create', $production->id);
            @endphp
            <div class="card p-4">
                <div>
                    <a href="{{ $vbProtokollRoute }}"
                       class="text-lg font-semibold text-gray-900 hover:text-orange-500">
                        {{ $production->bezeichnung }}
                    </a>

                    <p class="text-sm text-gray-500">
                        {{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '—' }}
                        –
                        {{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '—' }}
                    </p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-2">
                    <a href="{{ $vbProtokollRoute }}"
                       class="btn btn-secondary text-center">
                        VB-Protokoll
                    </a>

                    @if(Auth::user()->isUser())
                    <a href="{{ route('productions.show', $production->id) }}"
                       class="btn btn-primary text-center">
                        Packliste
                    </a>

                    <a href="{{ route('productions.edit', $production->id) }}"
                       class="btn btn-secondary text-center">
                        Bearbeiten
                    </a>

                    <a href="{{ route('productions.create', ['from' => $production->id]) }}"
                       class="btn btn-secondary text-center">
                        Duplizieren
                    </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="card card-pad text-center text-gray-500">
                Keine Produktionen gefunden.
            </div>
        @endforelse
    </div>

</div>