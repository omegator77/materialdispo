<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Geräte') }}
            </h2>
            @if(Auth::user()->isUser())
            <a href="{{ route('items.create') }}"
                class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                Neues Gerät
            </a>
            @endif
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
    @if(Auth::user()->isUser() && $productions->count())
    <div id="production-dropdown"
         class="hidden fixed bg-white border border-gray-200 rounded-lg shadow-xl z-50 overflow-hidden w-64">

        {{-- Zur Packliste --}}
        <div class="flex items-center gap-2 px-3 py-2 bg-blue-600">
            <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M12 12v4m0 0l-2-2m2 2l2-2"/>
            </svg>
            <span class="text-xs font-semibold text-white uppercase tracking-wide">Zur Packliste</span>
        </div>
        @foreach($productions as $production)
        <form method="POST" action="{{ route('productions.attachItem', $production->id) }}" class="production-attach-form">
            @csrf
            <input type="hidden" name="item_id" class="dropdown-item-id" value="">
            <button type="submit" class="w-full text-left px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-b border-gray-50 last:border-0 transition-colors">
                <span class="font-medium">{{ $production->bezeichnung }}</span>
                <span class="block text-xs text-gray-400 mt-0.5">
                    {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
                    – {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}
                </span>
            </button>
        </form>
        @endforeach

        {{-- Als Kamerazug (nur für Kameras) --}}
        <div id="camera-config-section" class="hidden border-t-2 border-gray-200">
            <div class="flex items-center gap-2 px-3 py-2 bg-orange-500">
                <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                </svg>
                <span class="text-xs font-semibold text-white uppercase tracking-wide">Als Kamerazug</span>
            </div>
            @foreach($productions as $production)
            <a class="camera-config-link block px-3 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-700 border-b border-gray-50 last:border-0 transition-colors"
               data-base-url="{{ route('camera-config.create', $production->id) }}"
               href="#">
                <span class="font-medium">{{ $production->bezeichnung }}</span>
                <span class="block text-xs text-gray-400 mt-0.5">
                    {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
                    – {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}
                </span>
            </a>
            @endforeach
        </div>

    </div>

    <script>
    (function () {
        const dropdown = document.getElementById('production-dropdown');
        let activeButton = null;

        const cameraSection = document.getElementById('camera-config-section');

        window.openProductionDropdown = function (itemId, unitId, btn) {
            if (activeButton === btn && !dropdown.classList.contains('hidden')) {
                closeDropdown();
                return;
            }

            // item_id in alle Packlisten-Forms setzen
            dropdown.querySelectorAll('.dropdown-item-id').forEach(input => {
                input.value = itemId;
            });

            // Kamerazug-Sektion: nur für Kameras (unit_id = 1)
            const isCamera = unitId === 1;
            cameraSection.classList.toggle('hidden', !isCamera);
            if (isCamera) {
                dropdown.querySelectorAll('.camera-config-link').forEach(link => {
                    link.href = link.dataset.baseUrl + '?camera_item_id=' + itemId;
                });
            }

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
