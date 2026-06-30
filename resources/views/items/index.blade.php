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

        {{-- Schnellsuche --}}
        <div class="px-2 py-2 border-b border-gray-100 bg-gray-50">
            <input type="text" id="production-dropdown-search"
                   placeholder="Produktion suchen…"
                   autocomplete="off"
                   class="form-control w-full text-sm py-1.5">
        </div>

        <div id="production-dropdown-list" class="max-h-80 overflow-y-auto">

            @foreach($productions as $production)
            <div class="production-option flex items-stretch border-b border-gray-50 last:border-0"
                 data-search="{{ \Illuminate\Support\Str::lower($production->bezeichnung) }}"
                 data-rank="{{ $loop->index }}">

                <form method="POST" action="{{ route('productions.attachItem', $production->id) }}"
                      class="production-attach-form flex-1 min-w-0">
                    @csrf
                    <input type="hidden" name="item_id" class="dropdown-item-id" value="">
                    <button type="submit" class="w-full text-left px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                        <span class="font-medium">{{ $production->bezeichnung }}</span>
                        <span class="block text-xs text-gray-400 mt-0.5">
                            {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
                            – {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}
                        </span>
                    </button>
                </form>

                <div class="camera-toggle-wrap hidden relative shrink-0 border-l border-gray-100"
                     data-base-url="{{ route('camera-config.create', $production->id) }}">
                    <button type="button" class="camera-toggle-btn h-full px-2 text-gray-400 hover:text-orange-600 hover:bg-orange-50" title="Weitere Optionen">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="camera-toggle-menu hidden absolute right-0 top-full mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                        <a class="camera-config-link block px-3 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-700 rounded-lg" href="#">
                            Als Kamerazug konfigurieren
                        </a>
                    </div>
                </div>
            </div>
            @endforeach

            @if($productions->count() > 3)
            <button type="button" class="production-show-more w-full text-center px-3 py-2 text-xs text-blue-600 hover:bg-blue-50 border-b border-gray-50">
                Alle {{ $productions->count() }} anzeigen
            </button>
            @endif

            <div id="production-dropdown-empty" class="hidden px-3 py-3 text-sm text-gray-400 text-center">
                Keine Produktion gefunden.
            </div>

        </div>

    </div>

    <script>
    (function () {
        const dropdown = document.getElementById('production-dropdown');
        let activeButton = null;

        const searchInput = document.getElementById('production-dropdown-search');
        const emptyState = document.getElementById('production-dropdown-empty');

        let expanded = false;

        function closeCameraMenus() {
            dropdown.querySelectorAll('.camera-toggle-menu').forEach(menu => menu.classList.add('hidden'));
        }

        function filterDropdown() {
            const needle = searchInput.value.trim().toLowerCase();
            const isSearching = needle !== '';
            let visibleCount = 0;

            dropdown.querySelectorAll('.production-option').forEach(el => {
                const visible = isSearching
                    ? el.dataset.search.includes(needle)
                    : (parseInt(el.dataset.rank, 10) < 3 || expanded);

                el.classList.toggle('hidden', !visible);
                if (visible) visibleCount++;
            });

            const showMoreBtn = dropdown.querySelector('.production-show-more');
            if (showMoreBtn) {
                showMoreBtn.classList.toggle('hidden', isSearching || expanded);
            }

            emptyState.classList.toggle('hidden', visibleCount > 0);
        }

        searchInput.addEventListener('input', filterDropdown);

        const showMoreBtn = dropdown.querySelector('.production-show-more');
        if (showMoreBtn) {
            showMoreBtn.addEventListener('click', () => { expanded = true; filterDropdown(); });
        }

        dropdown.querySelectorAll('.camera-toggle-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const menu = btn.nextElementSibling;
                const wasOpen = !menu.classList.contains('hidden');
                closeCameraMenus();
                menu.classList.toggle('hidden', wasOpen);
            });
        });

        window.openProductionDropdown = function (itemId, unitId, btn) {
            if (activeButton === btn && !dropdown.classList.contains('hidden')) {
                closeDropdown();
                return;
            }

            // item_id in alle Packlisten-Forms setzen
            dropdown.querySelectorAll('.dropdown-item-id').forEach(input => {
                input.value = itemId;
            });

            // Kamerazug-Option: nur für Kameras (unit_id = 1)
            const isCamera = unitId === 1;
            dropdown.querySelectorAll('.camera-toggle-wrap').forEach(wrap => {
                wrap.classList.toggle('hidden', !isCamera);
            });
            if (isCamera) {
                dropdown.querySelectorAll('.camera-config-link').forEach(link => {
                    link.href = link.closest('.camera-toggle-wrap').dataset.baseUrl + '?camera_item_id=' + itemId;
                });
            }

            // Suche & Aufklapp-Status zurücksetzen
            searchInput.value = '';
            expanded = false;
            closeCameraMenus();
            filterDropdown();

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
                return;
            }
            if (!e.target.closest('.camera-toggle-wrap')) {
                closeCameraMenus();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDropdown();
        });
    })();
    </script>
    @endif

</x-app-layout>
