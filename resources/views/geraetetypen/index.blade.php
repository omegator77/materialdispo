<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gerätetypen
            </h2>

            @if(Auth::user()->isUser())
            <a href="{{ route('geraetetypen.create') }}"
               class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                Neuer Gerätetyp
            </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl w-11/12 mx-auto mt-6">

        @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
        @endif

        {{-- Desktop / Tablet --}}
        <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="text-left px-4 py-3">Bezeichnung</th>
                        <th class="text-left px-4 py-3">Gruppe</th>
                        <th class="text-left px-4 py-3">Bemerkung</th>
                        <th class="text-left px-4 py-3">Geräte</th>
                        <th class="text-right px-4 py-3">Aktionen</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($geraetetypen as $geraetetyp)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $geraetetyp->bezeichnung }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $geraetetyp->unit->bezeichnung ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $geraetetyp->description ?: '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $geraetetyp->items_count }}
                            </td>
                            <td class="px-4 py-3">
                                @if(Auth::user()->isUser())
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('geraetetypen.edit', $geraetetyp->id) }}"
                                       class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-1 px-3 rounded">
                                        Bearbeiten
                                    </a>

                                    <form action="{{ route('geraetetypen.destroy', $geraetetyp->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Diesen Gerätetyp wirklich löschen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="bg-red-600 hover:bg-red-700 text-white font-semibold py-1 px-3 rounded">
                                            Löschen
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-6 text-gray-500">
                                Noch keine Gerätetypen angelegt.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Handy --}}
        <div class="md:hidden space-y-3">
            @forelse($geraetetypen as $geraetetyp)
                <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">
                    <div class="font-semibold text-gray-900">
                        {{ $geraetetyp->bezeichnung }}
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $geraetetyp->unit->bezeichnung ?? '—' }}
                    </div>
                    @if($geraetetyp->description)
                    <div class="text-sm text-gray-600 mt-1">
                        {{ $geraetetyp->description }}
                    </div>
                    @endif

                    @if(Auth::user()->isUser())
                    <div class="flex gap-2 mt-3">
                        <a href="{{ route('geraetetypen.edit', $geraetetyp->id) }}"
                           class="flex-1 text-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-1.5 px-3 rounded">
                            Bearbeiten
                        </a>
                        <form action="{{ route('geraetetypen.destroy', $geraetetyp->id) }}"
                              method="POST"
                              onsubmit="return confirm('Diesen Gerätetyp wirklich löschen?');"
                              class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-1.5 px-3 rounded">
                                Löschen
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            @empty
                <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6 text-center text-gray-500">
                    Noch keine Gerätetypen angelegt.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
