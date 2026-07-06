<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mietvorgänge
            </h2>

            @if(Auth::user()->isUser())
            <a href="{{ route('mietvorgaenge.create') }}"
               class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                Neuer Mietvorgang
            </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl w-11/12 mx-auto mt-6">

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        {{-- Desktop / Tablet --}}
        <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="text-left px-4 py-3">Vermieter</th>
                        <th class="text-left px-4 py-3">Zeitraum</th>
                        <th class="text-left px-4 py-3">Hinweg</th>
                        <th class="text-left px-4 py-3">Rückweg</th>
                        <th class="text-left px-4 py-3">Geräte</th>
                        <th class="text-right px-4 py-3">Aktionen</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($mietvorgaenge as $mietvorgang)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">
                                <a href="{{ route('mietvorgaenge.show', $mietvorgang) }}"
                                   class="text-gray-900 hover:text-orange-500">
                                    {{ $mietvorgang->supplier?->bezeichnung ?? 'Vermieter gelöscht' }}
                                </a>
                            </td>

                            <td class="px-4 py-3">
                                {{ \Carbon\Carbon::parse($mietvorgang->rent_start)->format('d.m.Y') }}
                                – {{ \Carbon\Carbon::parse($mietvorgang->rent_end)->format('d.m.Y') }}
                            </td>

                            <td class="px-4 py-3">
                                {{ \App\Models\Mietvorgang::TRANSPORT_TYPES_START[$mietvorgang->transport_type_start] ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ \App\Models\Mietvorgang::TRANSPORT_TYPES_END[$mietvorgang->transport_type_end] ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $mietvorgang->items_count }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('mietvorgaenge.show', $mietvorgang) }}"
                                       class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-1 px-3 rounded">
                                        Details
                                    </a>

                                    @if(Auth::user()->isUser())
                                    <form action="{{ route('mietvorgaenge.destroy', $mietvorgang) }}" method="POST"
                                          onsubmit="return confirm('Mietvorgang wirklich löschen? Das geht nur, wenn keine Geräte mehr zugeordnet sind.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1 px-3 rounded">
                                            Löschen
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-6 text-gray-500">
                                Noch keine Mietvorgänge vorhanden.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Handy --}}
        <div class="md:hidden space-y-3">

            @forelse($mietvorgaenge as $mietvorgang)

                <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">

                    <a href="{{ route('mietvorgaenge.show', $mietvorgang) }}"
                       class="text-lg font-semibold text-gray-900 hover:text-orange-500">
                        {{ $mietvorgang->supplier?->bezeichnung ?? 'Vermieter gelöscht' }}
                    </a>

                    <p class="text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($mietvorgang->rent_start)->format('d.m.Y') }}
                        – {{ \Carbon\Carbon::parse($mietvorgang->rent_end)->format('d.m.Y') }}
                    </p>

                    <div class="mt-3 space-y-1 text-sm text-gray-700">
                        <p><span class="font-medium">Hinweg:</span> {{ \App\Models\Mietvorgang::TRANSPORT_TYPES_START[$mietvorgang->transport_type_start] ?? '—' }}</p>
                        <p><span class="font-medium">Rückweg:</span> {{ \App\Models\Mietvorgang::TRANSPORT_TYPES_END[$mietvorgang->transport_type_end] ?? '—' }}</p>
                        <p><span class="font-medium">Geräte:</span> {{ $mietvorgang->items_count }}</p>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('mietvorgaenge.show', $mietvorgang) }}"
                           class="flex-1 text-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-3 rounded">
                            Details
                        </a>

                        @if(Auth::user()->isUser())
                        <form action="{{ route('mietvorgaenge.destroy', $mietvorgang) }}" method="POST"
                              class="flex-1" onsubmit="return confirm('Mietvorgang wirklich löschen? Das geht nur, wenn keine Geräte mehr zugeordnet sind.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded">
                                Löschen
                            </button>
                        </form>
                        @endif
                    </div>

                </div>

            @empty

                <div class="bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">
                    Noch keine Mietvorgänge vorhanden.
                </div>

            @endforelse

        </div>

    </div>
</x-app-layout>
