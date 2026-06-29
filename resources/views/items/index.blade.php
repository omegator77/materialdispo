<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Geräte') }}
            </h2>
            <a href="{{ route('items.create') }}"
                class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                Neues Gerät
            </a>
        </div>
    </x-slot>

    @php
        $tableMap = [
            1 => '_cameras',
            2 => '_lenses',
            9 => '_monitors',
            10 => '_monitors',
        ];
        $unitId = (int) request('unit_id');
        $partial = $tableMap[$unitId] ?? '_overview';
    @endphp

    @include('items.tables.' . $partial)

    {{-- Geteiltes Produktions-Dropdown (einmal im DOM, per JS positioniert) --}}
    @if($productions->count())
    <div id="production-dropdown"
         class="hidden fixed bg-white border border-gray-200 rounded-lg shadow-lg z-50 py-1 w-56"
         style="min-width: 14rem;">
        <div class="px-3 py-1.5 text-xs font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-100">
            Zur Produktion hinzufügen
        </div>
        @foreach($productions as $production)
        <form method="POST" action="{{ route('productions.attachItem', $production->id) }}" class="production-attach-form">
            @csrf
            <input type="hidden" name="item_id" class="dropdown-item-id" value="">
            <button type="submit" class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                {{ $production->bezeichnung }}
                <span class="block text-xs text-gray-400">
                    {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
                    – {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}
                </span>
            </button>
        </form>
        @endforeach
    </div>

    <script>
    (function () {
        const dropdown = document.getElementById('production-dropdown');
        let activeButton = null;

        window.openProductionDropdown = function (itemId, btn) {
            if (activeButton === btn && !dropdown.classList.contains('hidden')) {
                closeDropdown();
                return;
            }

            // item_id in alle Forms setzen
            dropdown.querySelectorAll('.dropdown-item-id').forEach(input => {
                input.value = itemId;
            });

            // Kurz sichtbar machen (off-screen) um Höhe zu messen
            dropdown.style.top  = '-9999px';
            dropdown.style.left = '-9999px';
            dropdown.classList.remove('hidden');

            const rect = btn.getBoundingClientRect();
            const dh   = dropdown.offsetHeight;
            const dw   = dropdown.offsetWidth;
            const spaceBelow = window.innerHeight - rect.bottom;

            // Vertikal: nach unten oder oben (fixed = relativ zum Viewport, kein scrollY)
            const top = spaceBelow >= dh + 8
                ? rect.bottom + 4
                : rect.top - dh - 4;

            // Horizontal: rechtsbündig am Button, aber nicht aus dem Fenster
            const left = Math.max(8, Math.min(
                rect.right - dw,
                window.innerWidth - dw - 8
            ));

            dropdown.style.top  = top + 'px';
            dropdown.style.left = left + 'px';
            activeButton = btn;
        };

        function closeDropdown() {
            dropdown.classList.add('hidden');
            activeButton = null;
        }

        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target) && !e.target.closest('[onclick^="openProductionDropdown"]')) {
                closeDropdown();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDropdown();
        });
    })();
    </script>
    @endif

</x-app-layout>
