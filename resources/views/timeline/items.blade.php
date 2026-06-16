<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Geräte-Timeline
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto px-4">

            <div class="bg-white shadow-sm rounded-lg p-4 mb-4">
                <form method="GET" action="{{ route('timeline.items') }}" class="flex flex-wrap gap-4 items-end">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Von</label>
                        <input type="date" name="start" value="{{ $start }}" class="mt-1 rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bis</label>
                        <input type="date" name="end" value="{{ $end }}" class="mt-1 rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gruppe</label>
                        <select name="unit_id" class="mt-1 rounded-md border-gray-300 shadow-sm">
                            <option value="">Alle Gruppen</option>

                            @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected((string)$unitId===(string)$unit->id)>
                                {{ $unit->bezeichnung }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Anzeigen
                        </button>
                    </div>

                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-4 overflow-x-auto">

                @php
                $timelineStart = \Carbon\Carbon::parse($start)->startOfDay();
                $timelineEnd = \Carbon\Carbon::parse($end)->startOfDay();

                $days = [];
                $cursor = $timelineStart->copy();

                while ($cursor->lte($timelineEnd)) {
                $days[] = $cursor->copy();
                $cursor->addDay();
                }

                $gridColumns = max(count($days), 1);
                @endphp

                <div class="min-w-[1400px]">

                    {{-- Kopfzeile --}}
                    <div
                        class="grid border-b border-gray-300 pb-2 mb-2 sticky top-0 bg-white z-10"
                        style="grid-template-columns: 15rem minmax(0, 1fr);">
                        <div class="font-bold text-sm">
                            Gerät
                        </div>

                        <div
                            class="grid"
                            style="grid-template-columns: repeat({{ $gridColumns }}, minmax(42px, 1fr));">
                            @foreach($days as $day)
                            <div class="text-center text-xs font-semibold {{ $day->isWeekend() ? 'text-red-600' : 'text-gray-700' }}">
                                <div>{{ $day->format('d.m') }}</div>
                                <div class="text-[10px] font-normal">
                                    {{ $day->locale('de')->isoFormat('dd') }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    @forelse($items->groupBy(fn($item) => $item->unit?->bezeichnung ?? 'Ohne Gruppe') as $unitName => $groupedItems)

                    <div
                        class="grid border-y border-gray-200"
                        style="grid-template-columns: 15rem minmax(0, 1fr);">
                        <div class="bg-gray-100 text-gray-700 font-semibold text-xs px-2 py-1">
                            {{ $unitName }}
                        </div>

                        <div class="bg-gray-100"></div>
                    </div>

                    @foreach($groupedItems as $item)

                    <div
                        class="grid border-b border-gray-200 min-h-8"
                        style="grid-template-columns: 15rem minmax(0, 1fr);">
                        {{-- Gerät --}}
                        <div class="px-2 py-1 text-xs bg-white flex items-center">
                            <span class="font-semibold">
                                {{ $item->nummer ?: '—' }}
                            </span>

                            <span class="mx-2 text-gray-400">•</span>

                            <span class="text-gray-700 truncate">
                                {{ $item->bezeichnung }}
                            </span>
                        </div>

                        {{-- Timeline --}}
                        <div class="relative h-8">

                            {{-- Hintergrundraster --}}
                            <div
                                class="absolute inset-0 grid"
                                style="grid-template-columns: repeat({{ $gridColumns }}, minmax(42px, 1fr));">
                                @foreach($days as $day)
                                <div class="border-l border-gray-200 {{ $day->isWeekend() ? 'bg-gray-50' : 'bg-white' }}"></div>
                                @endforeach
                            </div>

                            {{-- Produktionsbalken --}}
                            <div
                                class="absolute inset-0 grid pointer-events-none"
                                style="grid-template-columns: repeat({{ $gridColumns }}, minmax(42px, 1fr));">
                                @foreach($item->productions as $production)

                                @php
                                $prodStart = \Carbon\Carbon::parse($production->booking_start)->startOfDay();
                                $prodEnd = \Carbon\Carbon::parse($production->booking_end)->startOfDay();

                                $visibleStart = $prodStart->lt($timelineStart) ? $timelineStart : $prodStart;
                                $visibleEnd = $prodEnd->gt($timelineEnd) ? $timelineEnd : $prodEnd;

                                $offset = $timelineStart->diffInDays($visibleStart);
                                $length = $visibleStart->diffInDays($visibleEnd) + 1;
                                @endphp

                                <a
                                    href="{{ route('productions.show', $production->id) }}"
                                    class="pointer-events-auto self-center h-6 bg-blue-600 hover:bg-blue-700 text-white text-[11px] rounded px-1 flex items-center overflow-hidden whitespace-nowrap z-10"
                                    title="{{ $production->bezeichnung }} ({{ $prodStart->format('d.m.Y') }} - {{ $prodEnd->format('d.m.Y') }})"
                                    style="
                grid-column: {{ $offset + 1 }} / span {{ $length }};
                grid-row: 1;
            ">
                                    {{ $production->bezeichnung }}
                                </a>

                                @endforeach
                            </div> {{-- Produktionsbalken-Grid --}}
                        </div> {{-- relative h-8 --}}
                    </div> {{-- Gerätezeile --}}

                    @endforeach

                    @empty
                    <div class="p-6 text-gray-500">
                        Keine Geräte gefunden.
                    </div>
                    @endforelse

                </div>
            </div>

        </div>
    </div>
</x-app-layout>