<x-app-layout>
<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Productions') }}
    </h2>
</x-slot>

<div class="max-w-7xl w-4/5 mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">
    <div class="flex flex-wrap md:flex-nowrap gap-8">
        <div class="w-full md:w-1/2">
            <h1 class="font-bold text-2xl mb-4">Produktion: {{ $production->bezeichnung }}</h1>
            <p class="font-bold mb-4">Buchungszeitraum: {{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '/' }} bis {{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '/' }}</p>

            <h2 class="text-xl font-semibold mt-8 mb-4">Verfügbare Items hinzufügen</h2>
            <!-- Gruppenauswahl -->
            <form method="GET" action="{{ route('productions.show', $production->id) }}" class="mb-4">
                <label for="unit" class="block font-semibold mb-2">Gruppe filtern:</label>
                <select name="unit" id="unit" class="w-full p-2 border border-gray-300 rounded mb-4" onchange="this.form.submit()">
                    <option value="">Alle Gruppen</option>
                    @foreach ($allUnits as $unit)
                        <option value="{{ $unit->id }}" {{ request('unit') == $unit->id ? 'selected' : '' }}>{{ $unit->bezeichnung }}</option>
                    @endforeach
                </select>
            </form>

            <!-- Item-Auswahl -->
            <form id="item-selection-form" method="POST" action="">
                @csrf
                <label for="item_id" class="block font-semibold mb-2">Item auswählen:</label>
                <select name="item_id" id="item_id" class="w-full p-2 border border-gray-300 rounded mb-4" required>
                    <option value="">Bitte Item auswählen</option>
                    @foreach ($availableItems as $item)
                        <option value="{{ $item->id }}" data-unit-id="{{ $item->units_id }}">{{ $item->bezeichnung }} ({{ $item->nummer }})</option>
                    @endforeach
                </select>

        <div class="flex gap-2 mt-2">
  {{-- Standard hinzufügen (Form submit) --}}
  <button type="submit"
          id="add-button"
          class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
    Standard hinzufügen
  </button>

  {{-- Konfigurieren (Link; href wird dynamisch gesetzt) --}}
  <a href="#"
     id="config-button"
     class="bg-indigo-600 text-white font-bold py-2 px-4 rounded">
    Konfigurieren
  </a>
</div>

            </form>

            <script>

                
               itemSelect.addEventListener('change', function() {
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];
    const unitId = selectedOption.getAttribute('data-unit-id');
    const selectedItemId = selectedOption.value;

    if (unitId === '1') { // Kameras
        configButton.classList.remove('hidden');
        addButton.classList.add('hidden');
        configButton.href = `{{ route('camera-config.create', $production) }}?camera_item_id=${selectedItemId}`;
    } else {
        configButton.classList.add('hidden');
        addButton.classList.remove('hidden');
        form.action = `{{ route('productions.attachItem', $production->id) }}`;
    }
});


                function redirectToConfigPage() {
                    const selectedItemId = document.getElementById('item_id').value;

                    if (!selectedItemId) {
                        alert('Bitte wählen Sie ein Item aus.');
                        return;
                    }

                    document.getElementById('item-selection-form').submit();
                }
            </script>

        </div>

        <div class="w-full md:w-1/2">
    <h2 class="text-xl font-semibold mb-4">Gepacktes Material</h2>
    <ul class="list-disc pl-5 space-y-3">
        @foreach ($production->items as $item)
            <li class="flex items-center">
                <span class="flex-1">{{ $item->bezeichnung }} {{ $item->nummer }} ({{ $item->unit->bezeichnung }})</span>
                <form action="{{ route('productions.detachItem', [$production->id, $item->id]) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-bold">
                        Entfernen
                    </button>
                </form>
            </li>
        @endforeach

@php
  // Kleines Helferlein für "Bezeichnung (Nummer)"
  $label = function($it) {
      if (!$it) return '—';
      return $it->bezeichnung . ($it->nummer ? ' ('.$it->nummer.')' : '');
  };
@endphp

<ul class="space-y-3">
  @foreach ($production->cameraConfigs as $config)
    <li class="border border-gray-200 rounded-lg shadow-sm bg-white">
      <details class="group" @if($loop->first)  @endif>
        <summary class="flex items-center justify-between gap-3 cursor-pointer select-none p-3">
          <div class="min-w-0">
            <div class="text-sm text-gray-500">Kamera-Konfiguration {{ $config->cam_number ?? '—' }}</div>
            <div class="font-semibold truncate">
              {{ $label($config->item ?? null) }}
            </div>
          </div>

          <div class="flex items-center gap-2">
            {{-- kleiner Chevron --}}
            <svg class="h-5 w-5 text-gray-400 group-open:rotate-180 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.172l3.71-3.94a.75.75 0 011.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
            </svg>
          </div>
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
            @if(!empty($config->notes))
              <div class="md:col-span-2 flex gap-2">
                <span class="w-28 text-gray-500">Notiz</span>
                <span class="font-medium">{{ $config->notes }}</span>
              </div>
            @endif
          </div>

          <div class="mt-3 flex justify-end">
            <form action="{{ route('camera-config.destroy', [$config->id]) }}" method="POST">
              @csrf
              @method('DELETE')
              <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-semibold">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const itemSelect = document.getElementById('item_id');
    const configButton = document.getElementById('config-button');
    const addButton = document.getElementById('add-button');
    const form = document.getElementById('item-selection-form');

    itemSelect.addEventListener('change', function() {
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        const unitId = selectedOption.getAttribute('data-unit-id');
        const selectedItemId = selectedOption.value;

        if (unitId === '1') { // UNIT_ID 1 ist für Kameras
            configButton.classList.remove('hidden');
            addButton.classList.add('hidden');
            form.action = `{{ route('camera-config.create', [$production->id, 0]) }}`.replace('/0', `/${selectedItemId}`);
        } else {
            configButton.classList.add('hidden');
            addButton.classList.remove('hidden');
            form.action = `{{ route('productions.attachItem', $production->id) }}`;
        }
    });
});

function redirectToConfigPage() {
    const selectedItemId = document.getElementById('item_id').value;

    if (!selectedItemId) {
        alert('Bitte wählen Sie ein Item aus.');
        return;
    }

    const form = document.getElementById('item-selection-form');
    const action = form.action;

    window.location.href = action;
}

document.addEventListener('DOMContentLoaded', () => {
  const form          = document.querySelector('#item-selection-form');    // ggf. anpassen
  const itemSelect    = document.querySelector('#item_id');                // dein Kamera-/Item-Dropdown
  const configButton  = document.querySelector('#config-button');          // der neue <a>-Link
  const addButton     = document.querySelector('#add-button');             // Standard hinzufügen-Button

  // Basis-URL zur Create-Route (ohne Query)
  const baseConfigUrl = "{{ route('camera-config.create', $production) }}";

  function updateUI() {
    const opt          = itemSelect.options[itemSelect.selectedIndex];
    const selectedId   = itemSelect.value || "";
    const unitId       = opt ? opt.getAttribute('data-unit-id') : null;

    // Wenn Kamera (units_id == 1): Konfigurieren anzeigen + href setzen
    if (unitId === '1' && selectedId) {
      // href setzen
      configButton.href = `${baseConfigUrl}?camera_item_id=${encodeURIComponent(selectedId)}`;
      // sichtbar/aktiv
      configButton.classList.remove('hidden', 'opacity-50', 'pointer-events-none');
      // Standard-Add ausblenden (falls du das so willst)
      addButton?.classList.add('hidden');
    } else {
      // Link “deaktivieren”
      configButton.href = '#';
      configButton.classList.add('opacity-50', 'pointer-events-none');
      configButton.classList.remove('hidden'); // oder hidden lassen, wenn du lieber komplett ausblendest
      // Standard-Add wieder einblenden
      addButton?.classList.remove('hidden');
      // Falls du per Item hinzufügen route setzt:
      if (form) {
        form.action = "{{ route('productions.attachItem', $production->id) }}"; // ggf. anpassen/entfernen
      }
    }
  }

  // Wenn jemand auf einen “inaktiven” Link klickt, unterbinden:
  configButton.addEventListener('click', (e) => {
    if (!configButton.href || configButton.href.endsWith('#')) {
      e.preventDefault();
      alert('Bitte zuerst eine Kamera auswählen.');
    }
  });

  // Events setzen
  itemSelect.addEventListener('change', updateUI);

  // Initial (z. B. beim Zurücknavigieren mit vorausgewähltem Item)
  updateUI();
});
</script>

</x-app-layout>
