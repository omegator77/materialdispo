{{-- Handlungsbedarf: Befunde mit Ein-Klick-Aktion. Erwartet: $attention --}}
@php
    $canAct = Auth::user()->isUser();
    $dot = ['rot' => 'bg-red-500', 'gelb' => 'bg-amber-400'];
    $ring = ['rot' => 'border-red-200 bg-red-50', 'gelb' => 'border-amber-200 bg-amber-50'];
@endphp
<div class="bg-white rounded-lg shadow-sm p-5" x-data="{ all: false }">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-base font-semibold text-gray-900">
            Handlungsbedarf
            @if(count($attention))
                <span class="ml-1 text-xs font-medium bg-gray-900 text-white rounded-full px-2 py-0.5">{{ count($attention) }}</span>
            @endif
        </h3>
        <span class="text-xs text-gray-400">nächste 45 Tage</span>
    </div>

    @if(empty($attention))
        <p class="text-sm text-green-700">✓ Alles im grünen Bereich — keine Konflikte, keine offenen Bedarfe.</p>
    @else
        <ul class="space-y-2">
            @foreach($attention as $f)
            <li class="border rounded-lg px-3 py-2 {{ $ring[$f['severity']] }}" @if($loop->index >= 5) x-show="all" x-cloak @endif>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="min-w-0">
                        <a href="{{ $f['url'] }}" class="text-sm font-medium text-gray-900 hover:text-orange-600">
                            <span class="inline-block w-2 h-2 rounded-full mr-1.5 {{ $dot[$f['severity']] }}"></span>{{ $f['title'] }}
                        </a>
                        <p class="text-xs text-gray-600 mt-0.5">{{ $f['detail'] }}</p>
                    </div>
                    @if($canAct && $f['action'])
                        @if(($f['action']['method'] ?? 'POST') === 'GET')
                            <a href="{{ $f['action']['url'] }}" class="shrink-0 text-xs font-semibold bg-white border border-gray-300 hover:border-orange-400 hover:text-orange-600 rounded px-3 py-1.5 text-center">
                                {{ $f['action']['label'] }}
                            </a>
                        @else
                            <form method="POST" action="{{ $f['action']['url'] }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="w-full text-xs font-semibold bg-gray-900 hover:bg-gray-700 text-white rounded px-3 py-1.5">
                                    {{ $f['action']['label'] }}
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
        @if(count($attention) > 5)
            <button type="button" @click="all = !all" class="mt-2 text-xs text-gray-500 hover:text-gray-800">
                <span x-show="!all">{{ count($attention) - 5 }} weitere anzeigen</span>
                <span x-show="all" x-cloak>Weniger anzeigen</span>
            </button>
        @endif
    @endif
</div>
