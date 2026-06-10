<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Productions') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl w-4/5 mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">
        <div class="flex flex-wrap md:flex-nowrap gap-8">

            {{-- Linke Spalte: Produktion + Material hinzufügen --}}
            <div class="w-full md:w-1/2">
                <h1 class="font-bold text-2xl mb-4">
                    Produktion: {{ $production->bezeichnung }}
                </h1>

                <p class="font-bold mb-4">
                    Buchungszeitraum:
                    {{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '/' }}
                    bis
                    {{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '/' }}
                </p>

                <h2 class="text-xl font-semibold mt-8 mb-4">Items hinzufügen</h2>

                {{-- Filter: Gruppe + belegte Geräte anzeigen --}}
                <form method="GET" action="{{ route('productions.show', $production->id) }}" class="mb-4">
                    <label for="unit" class="block font-semibold mb-2">Gruppe filtern:</label>

                    <select
                        name="unit"
                        id="unit"
                        class="w-full p-2 border border-gray-300 rounded mb-3"
                        onchange="this.form.submit()"
                    >
                        <option value="">Alle Gruppen</option>

                        @foreach ($allUnits as $unit)
                            <option
                                value="{{ $unit->id }}"
                                {{ request('unit') == $unit->id ? 'selected' : '' }}
                            >
                                {{ $unit->bezeichnung }}
                            </option>
                        @endforeach
                    </select>

                    <label class="flex items-center gap-2 text-sm mb-4">
                        <input
                            type="checkbox"
                            name="show_unavailable"
                            value="1"
                            class="rounded border-gray-300"
                            onchange="this.form.submit()"
                            {{ request('show_unavailable') ? 'checked' : '' }}
                        >
                        Nicht verfügbare Geräte anzeigen
                    </label>
                </form>

                {{-- Item-Auswahl --}}
                <form id="item-selection-form" method="POST" action="">
    @csrf

    <input type="hidden" name="unit" value="{{ request('unit') }}">
    <input type="hidden" name="show_unavailable" value="{{ request('show_unavailable') }}">

                    <label for="item_id" class="block font-semibold mb-2">Item auswählen:</label>

                    <select
                        name="item_id"
                        id="item_id"
                        class="w-full p-2 border border-gray-300 rounded mb-4"
                        required
                    >
                        <option value="">Bitte Item auswählen</option>

                        @foreach ($availableItems as $item)
                            <option
                                value="{{ $item->id }}"
                                data-unit-id="{{ $item->units_id }}"
                                data-available="{{ $item->is_available ? '1' : '0' }}"
                                @disabled(! $item->is_available)
                            >
                                {{ $item->bezeichnung }} ({{ $item->nummer }})
                            </option>
                        @endforeach
                    </select>

                    <div class="flex flex-wrap gap-2 mt-2">
                        {{-- Standard hinzufügen: bucht das gewählte Item direkt --}}
                        <button
                            type="submit"
                            id="add-button"
                            class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Standard hinzufügen
                        </button>

                        {{-- Konfigurieren: nur bei Kameras sichtbar --}}
                        <a
                            href="#"
                            id="config-button"
                            class="hidden bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Konfigurieren
                        </a>
                    </div>
                </form>
            </div>

            {{-- Rechte Spalte: Gepacktes Material --}}
            <div class="w-full md:w-1/2">
                <h2 class="text-xl font-semibold mb-4">Gepacktes Material</h2>

                <ul class="list-disc pl-5 space-y-3">
                    @foreach ($production->items as $item)
                        <li class="flex items-center gap-3">
                            <span class="flex-1">
                                {{ $item->bezeichnung }} {{ $item->nummer }} ({{ $item->unit->bezeichnung }})
                            </span>

                            <form action="{{ route('productions.detachItem', [$production->id, $item->id]) }}" method="POST">
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-bold"
                                >
                                    Entfernen
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>

                @php
                    // Helfer für einheitliche Anzeige: Bezeichnung (Nummer)
                    $label = function ($it) {
                        if (! $it) {
                            return '—';
                        }

                        return $it->bezeichnung . ($it->nummer ? ' (' . $it->nummer . ')' : '');
                    };
                @endphp

                {{-- Kamera-Konfigurationen --}}
                <ul class="space-y-3 mt-4">
                    @foreach ($production->cameraConfigs as $config)
                        <li class="border border-gray-200 rounded-lg shadow-sm bg-white">
                            <details class="group">
                                <summary class="flex items-center justify-between gap-3 cursor-pointer select-none p-3">
                                    <div class="min-w-0">
                                        <div class="text-sm text-gray-500">
                                            Kamera-Konfiguration {{ $config->cam_number ?? '—' }}
                                        </div>

                                        <div class="font-semibold truncate">
                                            {{ $label($config->item ?? null) }}
                                        </div>
                                    </div>

                                    <svg
                                        class="h-5 w-5 text-gray-400 group-open:rotate-180 transition-transform"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.172l3.71-3.94a.75.75 0 011.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </summary>

                                <div class="px-3 pb-3">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                        <div class="flex gap-2">
                                            <span class="w-28 text-gray-500">Kamera</span>
                                            <span class="font-medium">{{ $label($config->item ?? null) }}</span>
                                        </div>

                                        <div class="flex gap-2">
                                            <span class="w-28 text-gray-500">Objektiv</span>
                                            <span class="font-medium">{{ $label($config->lensItem ?? null) }}</span>
                                        </div>

                                        <div class="flex gap-2">
                                            <span class="w-28 text-gray-500">Adapter</span>
                                            <span class="font-medium">{{ $label($config->adapterItem ?? null) }}</span>
                                        </div>

                                        <div class="flex gap-2">
                                            <span class="w-28 text-gray-500">Stativ</span>
                                            <span class="font-medium">{{ $label($config->tripodItem ?? null) }}</span>
                                        </div>

                                        <div class="flex gap-2">
                                            <span class="w-28 text-gray-500">Stativkopf</span>
                                            <span class="font-medium">{{ $label($config->headItem ?? null) }}</span>
                                        </div>

                                        @if (! empty($config->notes))
                                            <div class="md:col-span-2 flex gap-2">
                                                <span class="w-28 text-gray-500">Notiz</span>
                                                <span class="font-medium">{{ $config->notes }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex justify-end gap-2">
                                        <a
    href="{{ route('camera-config.edit', $config->id) }}"
    class="bg-blue-600 hover:bg-blue-700 text-white py-1 px-4 rounded font-semibold"
>
    Bearbeiten
</a>
                                        <form action="{{ route('camera-config.destroy', [$config->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-semibold"
                                            >
                                                Entfernen
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </details>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- Flash-Meldungen --}}
    @if (session('success'))
        <div id="success-message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mt-4 fixed top-4 right-4 z-50">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div id="error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mt-4 fixed top-4 right-4 z-50">
            {{ session('error') }}
        </div>
    @endif

    {{-- Button-Logik: Kamera kann einzeln oder als Konfiguration hinzugefügt werden --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('item-selection-form');
            const itemSelect = document.getElementById('item_id');
            const configButton = document.getElementById('config-button');
            const addButton = document.getElementById('add-button');

            const attachUrl = "{{ route('productions.attachItem', $production->id) }}";
            const configBaseUrl = "{{ route('camera-config.create', $production) }}";

            function updateButtons() {
                const selectedOption = itemSelect.options[itemSelect.selectedIndex];
                const selectedItemId = itemSelect.value;
                const unitId = selectedOption ? selectedOption.getAttribute('data-unit-id') : null;

                form.action = attachUrl;
                addButton.classList.remove('hidden');

                if (unitId === '1' && selectedItemId) {
                    configButton.href = `${configBaseUrl}?camera_item_id=${encodeURIComponent(selectedItemId)}`;
                    configButton.classList.remove('hidden');
                } else {
                    configButton.href = '#';
                    configButton.classList.add('hidden');
                }
            }

            configButton.addEventListener('click', (event) => {
                if (configButton.href.endsWith('#')) {
                    event.preventDefault();
                }
            });

            itemSelect.addEventListener('change', updateButtons);

            updateButtons();
        });
    </script>
</x-app-layout>