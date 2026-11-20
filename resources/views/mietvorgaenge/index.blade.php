<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Mietvorgänge">
            <x-slot name="actions">
                @if(Auth::user()->isUser())
            <a href="{{ route('mietvorgaenge.create') }}"
               class="btn btn-primary">
                Neuer Mietvorgang
            </a>
            @endif
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="page">

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
        <div class="hidden md:block card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bezeichnung</th>
                        <th>Zeitraum</th>
                        <th>Hinweg</th>
                        <th>Rückweg</th>
                        <th>Geräte</th>
                        <th class="text-right">Aktionen</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($mietvorgaenge as $mietvorgang)
                        <tr>
                            <td class="font-medium">
                                <a href="{{ route('mietvorgaenge.show', $mietvorgang) }}"
                                   class="text-gray-900 hover:text-orange-500">
                                    {{ $mietvorgang->bezeichnung ?? $mietvorgang->supplier?->bezeichnung ?? 'Vermieter gelöscht' }}
                                </a>
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($mietvorgang->rent_start)->format('d.m.Y') }}
                                – {{ \Carbon\Carbon::parse($mietvorgang->rent_end)->format('d.m.Y') }}
                            </td>

                            <td>
                                {{ $mietvorgang->transport_type_start ?: '—' }}
                            </td>

                            <td>
                                {{ $mietvorgang->transport_type_end ?: '—' }}
                            </td>

                            <td>
                                {{ $mietvorgang->items_count }}
                            </td>

                            <td>
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('mietvorgaenge.show', $mietvorgang) }}"
                                       class="btn btn-primary btn-sm">
                                        Details
                                    </a>

                                    @if(Auth::user()->isUser())
                                    <form action="{{ route('mietvorgaenge.destroy', $mietvorgang) }}" method="POST"
                                          onsubmit="return confirmDeleteVorgang(this, {{ $mietvorgang->items_count }});">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="force" value="0">
                                        <button type="submit"
                                                class="btn btn-danger btn-sm">
                                            Löschen
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
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

                <div class="card p-4">

                    <a href="{{ route('mietvorgaenge.show', $mietvorgang) }}"
                       class="text-lg font-semibold text-gray-900 hover:text-orange-500">
                        {{ $mietvorgang->supplier?->bezeichnung ?? 'Vermieter gelöscht' }}
                    </a>

                    <p class="text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($mietvorgang->rent_start)->format('d.m.Y') }}
                        – {{ \Carbon\Carbon::parse($mietvorgang->rent_end)->format('d.m.Y') }}
                    </p>

                    <div class="mt-3 space-y-1 text-sm text-gray-700">
                        <p><span class="font-medium">Hinweg:</span> {{ $mietvorgang->transport_type_start ?: '—' }}</p>
                        <p><span class="font-medium">Rückweg:</span> {{ $mietvorgang->transport_type_end ?: '—' }}</p>
                        <p><span class="font-medium">Geräte:</span> {{ $mietvorgang->items_count }}</p>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('mietvorgaenge.show', $mietvorgang) }}"
                           class="btn btn-primary flex-1 text-center">
                            Details
                        </a>

                        @if(Auth::user()->isUser())
                        <form action="{{ route('mietvorgaenge.destroy', $mietvorgang) }}" method="POST"
                              class="flex-1" onsubmit="return confirmDeleteVorgang(this, {{ $mietvorgang->items_count }});">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="force" value="0">
                            <button type="submit"
                                    class="btn btn-danger w-full text-center">
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

    <script>
        function confirmDeleteVorgang(form, itemsCount) {
            if (itemsCount > 0) {
                if (!confirm(`Diesem Mietvorgang sind noch ${itemsCount} Gerät(e) zugeordnet. Sollen alle Geräte entfernt und der Vorgang anschließend gelöscht werden?`)) {
                    return false;
                }
                form.querySelector('input[name="force"]').value = '1';
                return true;
            }

            return confirm('Mietvorgang wirklich löschen?');
        }
    </script>
</x-app-layout>
