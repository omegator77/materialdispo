@isset($selectedProduction)
    @if ($selectedProduction)
        <div class="max-w-7xl w-11/12 mx-auto mt-6">
            <a href="{{ route('productions.index') }}"
               class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                Alle Produktionen anzeigen
            </a>
        </div>
    @endif
@endisset

<div class="max-w-7xl w-11/12 mx-auto mt-6">

    {{-- Desktop / Tablet --}}
    <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-left px-4 py-3">Produktion</th>
                    <th class="text-left px-4 py-3">Beginn</th>
                    <th class="text-left px-4 py-3">Ende</th>
                    <th class="text-right px-4 py-3">Aktionen</th>
                </tr>
            </thead>

            <tbody>
                @forelse($productions as $production)
                    @php
                        $vbProtokollRoute = $production->vbProtokoll
                            ? route('vb-protokoll.show', $production->id)
                            : route('vb-protokoll.create', $production->id);
                    @endphp
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ $vbProtokollRoute }}"
                               class="text-gray-900 hover:text-orange-500">
                                {{ $production->bezeichnung }}
                            </a>
                        </td>

                        <td class="px-4 py-3">
                            {{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '—' }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ $vbProtokollRoute }}"
                                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1 px-3 rounded">
                                    VB-Protokoll
                                </a>

                                @if(Auth::user()->isUser())
                                <a href="{{ route('productions.show', $production->id) }}"
                                   class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-1 px-3 rounded">
                                    Zuordnen
                                </a>

                                <a href="{{ route('productions.edit', $production->id) }}"
                                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1 px-3 rounded">
                                    Bearbeiten
                                </a>

                                @isset($ShowDelete)
                                    @if ($ShowDelete)
                                        <form action="{{ route('productions.destroy', $production->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Diese Produktion wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="bg-red-600 hover:bg-red-700 text-white font-semibold py-1 px-3 rounded">
                                                Löschen
                                            </button>
                                        </form>
                                    @endif
                                @endisset
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-6 text-gray-500">
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
            <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">
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
                       class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded">
                        VB-Protokoll
                    </a>

                    @if(Auth::user()->isUser())
                    <a href="{{ route('productions.show', $production->id) }}"
                       class="text-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-3 rounded">
                        Zuordnen
                    </a>

                    <a href="{{ route('productions.edit', $production->id) }}"
                       class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded">
                        Bearbeiten
                    </a>

                    @isset($ShowDelete)
                        @if ($ShowDelete)
                            <form action="{{ route('productions.destroy', $production->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Diese Produktion wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.');">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-3 rounded">
                                    Löschen
                                </button>
                            </form>
                        @endif
                    @endisset
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">
                Keine Produktionen gefunden.
            </div>
        @endforelse
    </div>

</div>