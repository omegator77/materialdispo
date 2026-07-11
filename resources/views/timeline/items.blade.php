<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Geräte-Timeline
            </h2>
            {{-- Zoom-Steuerung (Desktop sichtbar, Mobile im Timeline-Header) --}}
            <div class="hidden sm:flex items-center gap-4">
                <div class="flex items-center gap-3 text-xs text-gray-600">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-blue-600 inline-block"></span> Produktion</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-amber-200 inline-block"></span> Gemietet</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-purple-600 inline-block"></span> Vermietet</span>
                </div>
                <div class="flex items-center gap-2" id="zoom-controls-header">
                    <span class="text-xs text-gray-500">Zoom:</span>
                    <button onclick="timelineZoom(-1)" class="w-7 h-7 rounded border border-gray-300 bg-white hover:bg-gray-100 flex items-center justify-center text-sm font-bold leading-none">−</button>
                    <span id="zoom-label-header" class="text-xs font-mono w-10 text-center text-gray-700">100%</span>
                    <button onclick="timelineZoom(+1)" class="w-7 h-7 rounded border border-gray-300 bg-white hover:bg-gray-100 flex items-center justify-center text-sm font-bold leading-none">+</button>
                    <button onclick="timelineZoomReset()" class="text-xs text-blue-600 hover:underline ml-1">Reset</button>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto px-4">

            {{-- Filter --}}
            <div class="bg-white shadow-sm rounded-lg p-4 mb-4">
                <form method="GET" action="{{ route('timeline.items') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Von</label>
                        <input type="date" name="start" value="{{ $start }}"
                            class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bis</label>
                        <input type="date" name="end" value="{{ $end }}"
                            class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gruppe</label>
                        <select name="unit_id" class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">Alle Gruppen</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string)$unitId === (string)$unit->id)>
                                    {{ $unit->bezeichnung }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Buchungsstatus</label>
                        <select name="booking_status" class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="all" @selected((string)$bookingStatus === 'all')>Alle</option>
                            <option value="booked" @selected((string)$bookingStatus === 'booked')>Nur gebucht</option>
                            <option value="free" @selected((string)$bookingStatus === 'free')>Nur frei</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                            Anzeigen
                        </button>
                    </div>
                </form>
            </div>

            {{-- Timeline-Container --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">

                {{-- Mobile Zoom-Bar --}}
                <div class="sm:hidden flex items-center justify-between px-3 py-2 border-b border-gray-200 bg-gray-50">
                    <span class="text-xs text-gray-500">Zoom &amp; Scrollen</span>
                    <div class="flex items-center gap-2">
                        <button onclick="timelineZoom(-1)"
                            class="w-8 h-8 rounded border border-gray-300 bg-white flex items-center justify-center text-base font-bold">−</button>
                        <span id="zoom-label-mobile" class="text-xs font-mono w-10 text-center text-gray-700">100%</span>
                        <button onclick="timelineZoom(+1)"
                            class="w-8 h-8 rounded border border-gray-300 bg-white flex items-center justify-center text-base font-bold">+</button>
                    </div>
                </div>

                {{-- Scrollbarer Bereich --}}
                <div id="timeline-scroll"
                    class="overflow-x-auto overflow-y-auto"
                    style="max-height: 75vh; cursor: grab; user-select: none;"
                >
                    @php
                        $timelineStart = \Carbon\Carbon::parse($start)->startOfDay();
                        $timelineEnd   = \Carbon\Carbon::parse($end)->startOfDay();
                        $days = [];
                        $cursor = $timelineStart->copy();
                        while ($cursor->lte($timelineEnd)) {
                            $days[] = $cursor->copy();
                            $cursor->addDay();
                        }
                        $today = \Carbon\Carbon::today();
                    @endphp

                    {{-- Innere Breite wird per JS gesteuert --}}
                    <div id="timeline-inner" style="min-width: max-content;" x-data="{ groups: {} }">

                        {{-- Sticky Kopfzeile --}}
                        <div id="timeline-header"
                            class="flex border-b border-gray-300 bg-white sticky top-0 z-20"
                        >
                            {{-- Fixe Geräte-Spalte --}}
                            <div id="col-device-header"
                                class="shrink-0 font-bold text-xs px-2 py-2 bg-white border-r border-gray-200 flex items-end sticky left-0 z-30"
                                style="width: var(--col-width, 200px);">
                                Gerät
                            </div>
                            {{-- Tage --}}
                            <div id="days-header" class="flex">
                                @foreach($days as $day)
                                    @php $isToday = $day->isSameDay($today); @endphp
                                    <div class="day-col text-center border-l border-gray-200 px-1 py-1 flex flex-col justify-end
                                        {{ $day->isWeekend() ? 'bg-red-50' : 'bg-white' }}
                                        {{ $isToday ? 'ring-2 ring-inset ring-blue-400' : '' }}"
                                        style="width: var(--day-width, 44px); min-width: var(--day-width, 44px);"
                                    >
                                        <div class="text-[11px] font-semibold leading-tight
                                            {{ $day->isWeekend() ? 'text-red-600' : ($isToday ? 'text-blue-700' : 'text-gray-700') }}">
                                            {{ $day->format('d.m') }}
                                        </div>
                                        <div class="text-[9px] leading-tight text-gray-400">
                                            {{ $day->locale('de')->isoFormat('dd') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Gruppen & Geräte --}}
                        @forelse($groupedItems as $group)
                            @php $gi = $loop->index; $unitName = $group['unit']->bezeichnung ?? 'Ohne Gruppe'; $groupItems = $group['items']; @endphp

                                {{-- Gruppenheader --}}
                                <div class="flex border-y border-gray-200" style="min-height: 28px;">
                                    <button type="button" @click="groups[{{ $gi }}] = !(groups[{{ $gi }}] ?? true)"
                                        class="shrink-0 flex items-center gap-2 px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 sticky left-0 z-20"
                                        style="width: var(--col-width, 200px);"
                                    >
                                        <span x-text="(groups[{{ $gi }}] ?? true) ? '▼' : '▶'" class="text-[10px]"></span>
                                        <span>{{ $unitName }}</span>
                                        <span class="text-gray-400 font-normal">({{ $groupItems->count() }})</span>
                                    </button>
                                    <div class="flex">
                                        @foreach($days as $day)
                                            <div class="day-col border-l border-gray-200 {{ $day->isWeekend() ? 'bg-gray-200/60' : 'bg-gray-100' }}"
                                                style="width: var(--day-width, 44px); min-width: var(--day-width, 44px);"></div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Gerätezeilen --}}
                                <div x-show="groups[{{ $gi }}] ?? true"
                                    x-transition:enter="transition-opacity duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition-opacity duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                                    @foreach($groupItems as $item)
                                        <div class="flex border-b border-gray-100 hover:bg-blue-50/30 transition-colors"
                                            style="min-height: 36px;">

                                            {{-- Gerätename – sticky links --}}
                                            <a href="{{ route('items.show', $item->id) }}"
                                                class="shrink-0 flex items-center px-2 py-1 text-xs bg-white hover:bg-blue-50 border-r border-gray-200 sticky left-0 z-20 transition-colors"
                                                style="width: var(--col-width, 200px);"
                                                title="Gerät öffnen">
                                                <span class="font-semibold text-gray-800 whitespace-nowrap">
                                                    {{ $item->nummer ?: '—' }}
                                                </span>
                                                <span class="mx-1.5 text-gray-300">•</span>
                                                <span class="text-gray-600 truncate">
                                                    {{ $item->bezeichnung }}
                                                </span>
                                            </a>

                                            {{-- Timeline-Zellen --}}
                                            <div class="relative flex" style="height: 36px;">

                                                {{-- Hintergrundraster --}}
                                                @foreach($days as $day)
                                                    @php $isToday = $day->isSameDay($today); @endphp
                                                    <div class="day-col border-l border-gray-100 h-full shrink-0
                                                        {{ $day->isWeekend() ? 'bg-red-50/60' : 'bg-white' }}
                                                        {{ $isToday ? 'bg-blue-50' : '' }}"
                                                        style="width: var(--day-width, 44px); min-width: var(--day-width, 44px);">
                                                    </div>
                                                @endforeach

                                                {{-- Gemietet (eingehend) — Hintergrundbalken, damit Produktions-/Vermietet-Balken darüber liegen. Ein Balken pro Mietvorgang, ein Gerät kann mehrere gleichzeitig gültige, nicht überlappende Mietvorgänge haben. --}}
                                                <div class="absolute inset-0 flex items-center pointer-events-none px-0">
                                                    @foreach($item->mietvorgaenge as $mietvorgang)
                                                        @php
                                                            $rentStart = $mietvorgang->rent_start->copy()->startOfDay();
                                                            $rentEnd   = $mietvorgang->rent_end->copy()->startOfDay();
                                                        @endphp
                                                        @if($rentEnd->gte($timelineStart) && $rentStart->lte($timelineEnd))
                                                            @php
                                                                $visStart = $rentStart->lt($timelineStart) ? $timelineStart : $rentStart;
                                                                $visEnd   = $rentEnd->gt($timelineEnd)     ? $timelineEnd   : $rentEnd;
                                                                $offset   = $timelineStart->diffInDays($visStart);
                                                                $length   = $visStart->diffInDays($visEnd) + 1;
                                                            @endphp
                                                            <div class="rent-bar timeline-range-bar absolute pointer-events-auto h-full bg-amber-200/70 border-x border-amber-400/60 z-0"
                                                                title="Gemietet von {{ $mietvorgang->supplier->bezeichnung ?? 'Vermieter gelöscht' }} ({{ $rentStart->format('d.m.Y') }} – {{ $rentEnd->format('d.m.Y') }})"
                                                                data-offset="{{ $offset }}"
                                                                data-length="{{ $length }}"
                                                            ></div>
                                                        @endif
                                                    @endforeach
                                                </div>

                                                {{-- Produktionsbalken + Vermietet (ausgehend) — klickbare Balken oberhalb. Ein Balken pro Vermietvorgang. --}}
                                                <div class="absolute inset-0 flex items-center pointer-events-none px-0">
                                                    @foreach($item->vermietvorgaenge as $vermietvorgang)
                                                        @php
                                                            $verleihStart = $vermietvorgang->rent_start->copy()->startOfDay();
                                                            $verleihEnd   = $vermietvorgang->rent_end->copy()->startOfDay();
                                                        @endphp
                                                        @if($verleihEnd->gte($timelineStart) && $verleihStart->lte($timelineEnd))
                                                            @php
                                                                $visStart = $verleihStart->lt($timelineStart) ? $timelineStart : $verleihStart;
                                                                $visEnd   = $verleihEnd->gt($timelineEnd)     ? $timelineEnd     : $verleihEnd;
                                                                $offset   = $timelineStart->diffInDays($visStart);
                                                                $length   = $visStart->diffInDays($visEnd) + 1;
                                                            @endphp
                                                            <a href="{{ route('vermietvorgaenge.show', $vermietvorgang) }}"
                                                                class="verleih-bar timeline-range-bar absolute pointer-events-auto flex items-center h-6 rounded px-1.5
                                                                       bg-purple-600 hover:bg-purple-700 active:bg-purple-800
                                                                       text-white text-[11px] font-medium
                                                                       overflow-hidden whitespace-nowrap
                                                                       shadow-sm transition-colors z-10
                                                                       focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-1"
                                                                title="Vermietet an {{ $vermietvorgang->mieter->bezeichnung ?? 'Mieter gelöscht' }} ({{ $verleihStart->format('d.m.Y') }} – {{ $verleihEnd->format('d.m.Y') }})"
                                                                data-offset="{{ $offset }}"
                                                                data-length="{{ $length }}"
                                                                style="top: 50%; transform: translateY(-50%);"
                                                            >
                                                                <span class="bar-label">Vermietet: {{ $vermietvorgang->mieter->bezeichnung ?? 'Mieter gelöscht' }}</span>
                                                            </a>
                                                        @endif
                                                    @endforeach

                                                    @foreach($item->productions as $production)
                                                        @php
                                                            $prodStart   = \Carbon\Carbon::parse($production->booking_start)->startOfDay();
                                                            $prodEnd     = \Carbon\Carbon::parse($production->booking_end)->startOfDay();
                                                            $visStart    = $prodStart->lt($timelineStart) ? $timelineStart : $prodStart;
                                                            $visEnd      = $prodEnd->gt($timelineEnd)     ? $timelineEnd   : $prodEnd;
                                                            $offset      = $timelineStart->diffInDays($visStart);
                                                            $length      = $visStart->diffInDays($visEnd) + 1;
                                                        @endphp
                                                        <a href="{{ route('productions.show', $production->id) }}"
                                                            class="production-bar absolute pointer-events-auto flex items-center h-6 rounded px-1.5
                                                                   bg-blue-600 hover:bg-blue-700 active:bg-blue-800
                                                                   text-white text-[11px] font-medium
                                                                   overflow-hidden whitespace-nowrap
                                                                   shadow-sm transition-colors z-10
                                                                   focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1"
                                                            title="{{ $production->bezeichnung }} ({{ $prodStart->format('d.m.Y') }} – {{ $prodEnd->format('d.m.Y') }})"
                                                            data-offset="{{ $offset }}"
                                                            data-length="{{ $length }}"
                                                            style="top: 50%; transform: translateY(-50%);"
                                                        >
                                                            <span class="bar-label">{{ $production->bezeichnung }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>

                                            </div>
                                        </div>
                                    @endforeach

                                </div>

                        @empty
                            <div class="p-8 text-center text-gray-400 text-sm">
                                Keine Geräte gefunden.
                            </div>
                        @endforelse

                    </div>
                </div>

                {{-- Scrollbar-Hint Mobile --}}
                <div class="sm:hidden text-center py-1 text-[10px] text-gray-400 border-t border-gray-100">
                    ← Horizontal scrollen oder pinch-to-zoom →
                </div>
            </div>

        </div>
    </div>

    {{-- ===================== JAVASCRIPT ===================== --}}
    <script>
    (function () {
        // ── Zoom-Stufen in px pro Tag
        const ZOOM_STEPS = [24, 32, 44, 60, 80, 110, 150];
        const DEFAULT_STEP = 2; // Index für 44px = 100%
        let currentStep = DEFAULT_STEP;

        const COL_WIDTH_BASE = 200; // px – Gerätespalte

        function dayWidth() { return ZOOM_STEPS[currentStep]; }

        function applyZoom() {
            const dw = dayWidth();
            document.documentElement.style.setProperty('--day-width', dw + 'px');
            document.documentElement.style.setProperty('--col-width', COL_WIDTH_BASE + 'px');

            // Balken neu positionieren
            document.querySelectorAll('.production-bar, .timeline-range-bar').forEach(bar => {
                const offset = parseInt(bar.dataset.offset);
                const length = parseInt(bar.dataset.length);
                bar.style.left  = (offset * dw) + 'px';
                bar.style.width = (length * dw - 2) + 'px';
            });

            // Label ausblenden wenn Balken zu schmal
            document.querySelectorAll('.bar-label').forEach(label => {
                label.style.display = dw < 36 ? 'none' : '';
            });

            // Zoom-Anzeige
            const pct = Math.round((dw / ZOOM_STEPS[DEFAULT_STEP]) * 100) + '%';
            ['zoom-label-header', 'zoom-label-mobile'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = pct;
            });
        }

        window.timelineZoom = function (dir) {
            currentStep = Math.max(0, Math.min(ZOOM_STEPS.length - 1, currentStep + dir));
            applyZoom();
        };

        window.timelineZoomReset = function () {
            currentStep = DEFAULT_STEP;
            applyZoom();
        };

        // ── Pinch-to-Zoom (Touch)
        let lastPinchDist = null;

        const scroll = document.getElementById('timeline-scroll');

        scroll.addEventListener('touchstart', e => {
            if (e.touches.length === 2) {
                lastPinchDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
            }
        }, { passive: true });

        scroll.addEventListener('touchmove', e => {
            if (e.touches.length === 2 && lastPinchDist !== null) {
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                if (dist - lastPinchDist > 30) {
                    timelineZoom(+1);
                    lastPinchDist = dist;
                } else if (lastPinchDist - dist > 30) {
                    timelineZoom(-1);
                    lastPinchDist = dist;
                }
            }
        }, { passive: true });

        scroll.addEventListener('touchend', () => { lastPinchDist = null; });

        // ── Maus-Drag zum Scrollen (Desktop)
        let isDragging = false, startX, startY, scrollLeft, scrollTop;

        scroll.addEventListener('mousedown', e => {
            // Nicht auslösen wenn man auf einen Link klickt
            if (e.target.closest('a, button')) return;
            isDragging = true;
            startX = e.pageX - scroll.offsetLeft;
            startY = e.pageY - scroll.offsetTop;
            scrollLeft = scroll.scrollLeft;
            scrollTop  = scroll.scrollTop;
            scroll.style.cursor = 'grabbing';
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
            scroll.style.cursor = 'grab';
        });

        document.addEventListener('mousemove', e => {
            if (!isDragging) return;
            e.preventDefault();
            const x = e.pageX - scroll.offsetLeft;
            const y = e.pageY - scroll.offsetTop;
            scroll.scrollLeft = scrollLeft - (x - startX);
            scroll.scrollTop  = scrollTop  - (y - startY);
        });

        // ── Mausrad-Zoom (Ctrl+Scroll)
        scroll.addEventListener('wheel', e => {
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                timelineZoom(e.deltaY < 0 ? +1 : -1);
            }
        }, { passive: false });

        // ── Initialisierung
        applyZoom();

        // ── Heute-Linie: scroll zu heute beim Laden
        @php
            $todayIndex = null;
            foreach ($days as $i => $d) {
                if ($d->isSameDay($today)) { $todayIndex = $i; break; }
            }
        @endphp
        const todayIndex = {{ $todayIndex !== null ? $todayIndex : 'null' }};
        if (todayIndex !== null) {
            setTimeout(() => {
                const dw = dayWidth();
                scroll.scrollLeft = Math.max(0, (todayIndex * dw) - 80);
            }, 50);
        }

    })();
    </script>

    <style>
        #timeline-scroll { -webkit-overflow-scrolling: touch; }
    </style>
    <script>
    document.addEventListener('keydown', e => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') return;
        if (e.key === '+' || e.key === '=') { e.preventDefault(); timelineZoom(+1); }
        if (e.key === '-' || e.key === '_') { e.preventDefault(); timelineZoom(-1); }
        if (e.key === '0')                  { e.preventDefault(); timelineZoomReset(); }
    });
    </script>

</x-app-layout>