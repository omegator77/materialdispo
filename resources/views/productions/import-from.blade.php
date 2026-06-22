<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Geräte aus Vorlage übernehmen
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto mt-6 px-4 pb-12">

        {{-- Info-Banner --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 mb-6 text-sm text-blue-800">
            <strong>{{ $production->bezeichnung }}</strong>
            ({{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }} –
            {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }})
            &larr; Vorlage: <strong>{{ $source->bezeichnung }}</strong>
        </div>

        <form action="{{ route('productions.storeImport', [$production, $source]) }}" method="POST">
            @csrf

            {{-- Kamerazüge --}}
            @if($configResults->count())
            <div class="mb-8">
                <h3 class="text-base font-semibold text-gray-800 mb-3">Kamerazüge</h3>
                <div class="space-y-3">
                    @foreach($configResults as $result)
                    @php $config = $result['config']; @endphp
                    <div class="bg-white border rounded-lg p-4 {{ $result['available'] ? 'border-gray-200' : 'border-yellow-300' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="font-semibold text-gray-800 mb-2">{{ $config->cam_number }}</div>
                                <div class="text-xs text-gray-500 space-y-0.5">
                                    @foreach($result['slots'] as $label => $item)
                                        <div class="{{ isset($result['conflicts'][$label]) ? 'text-red-600 font-medium' : '' }}">
                                            <span class="text-gray-400">{{ $label }}:</span>
                                            {{ $item->bezeichnung }}{{ $item->nummer ? ' ('.$item->nummer.')' : '' }}
                                            @if(isset($result['conflicts'][$label]))
                                                — {{ $result['conflicts'][$label]['reason'] }}
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="shrink-0 text-sm">
                                @if($result['available'])
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="configs[{{ $config->id }}]" value="import" checked
                                               class="rounded border-gray-300 text-blue-600">
                                        <span class="text-gray-700">Übernehmen</span>
                                    </label>
                                @else
                                    <span class="text-yellow-700 text-xs font-medium">Nicht vollständig verfügbar — wird übersprungen</span>
                                    <input type="hidden" name="configs[{{ $config->id }}]" value="skip">
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Einzelgeräte --}}
            @if($itemResults->count())
            <div class="mb-8">
                <h3 class="text-base font-semibold text-gray-800 mb-3">Weitere Geräte</h3>
                <div class="space-y-3">
                    @foreach($itemResults as $result)
                    @php $item = $result['item']; @endphp
                    <div class="bg-white border rounded-lg p-4 {{ $result['available'] ? 'border-gray-200' : 'border-yellow-300' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="font-semibold text-gray-800">
                                    {{ $item->bezeichnung }}{{ $item->nummer ? ' ('.$item->nummer.')' : '' }}
                                </div>
                                <div class="text-xs text-gray-400">{{ $item->unit?->bezeichnung }}</div>
                                @if($result['notes'])
                                    <div class="text-xs text-gray-500 mt-1">Notiz: {{ $result['notes'] }}</div>
                                @endif
                            </div>

                            <div class="shrink-0 text-sm min-w-[220px]">
                                @if($result['available'])
                                    {{-- Verfügbar: Checkbox zum Übernehmen --}}
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox"
                                               class="rounded border-gray-300 text-blue-600 peer"
                                               onchange="this.nextElementSibling.value = this.checked ? 'keep' : 'skip'"
                                               checked>
                                        <input type="hidden" name="items[{{ $item->id }}][action]" value="keep">
                                        <span class="text-gray-700">Übernehmen</span>
                                    </label>
                                    @if($result['notes'])
                                        <input type="hidden" name="items[{{ $item->id }}][notes]" value="{{ $result['notes'] }}">
                                    @endif
                                @else
                                    {{-- Nicht verfügbar: Überspringen oder Ersetzen --}}
                                    <div class="text-yellow-700 text-xs font-medium mb-2">{{ $result['reason'] }}</div>
                                    <div class="space-y-1.5" x-data="{ action: 'skip' }">
                                        <label class="flex items-center gap-2 cursor-pointer text-gray-700">
                                            <input type="radio" name="item_action_{{ $item->id }}" x-model="action" value="skip" class="border-gray-300">
                                            Überspringen
                                        </label>
                                        @if($result['alternatives']->count())
                                        <label class="flex items-center gap-2 cursor-pointer text-gray-700">
                                            <input type="radio" name="item_action_{{ $item->id }}" x-model="action" value="replace" class="border-gray-300">
                                            Ersetzen durch:
                                        </label>
                                        <select name="items[{{ $item->id }}][replacement_id]"
                                                x-show="action === 'replace'"
                                                class="form-control w-full text-sm mt-1">
                                            @foreach($result['alternatives'] as $alt)
                                                <option value="{{ $alt->id }}">
                                                    {{ $alt->bezeichnung }}{{ $alt->nummer ? ' ('.$alt->nummer.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @else
                                        <span class="text-xs text-gray-400 ml-5">Kein verfügbarer Ersatz vorhanden</span>
                                        @endif
                                        <input type="hidden" name="items[{{ $item->id }}][action]" :value="action">
                                        @if($result['notes'])
                                            <input type="hidden" name="items[{{ $item->id }}][notes]" value="{{ $result['notes'] }}">
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($itemResults->isEmpty() && $configResults->isEmpty())
            <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-400">
                Die Vorlage enthält keine Geräte.
            </div>
            @endif

            {{-- Aktionen --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <a href="{{ route('productions.show', $production) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 hover:underline">
                    Überspringen — direkt zur Produktion
                </a>
                <button type="submit"
                        class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-6 rounded focus:outline-none focus:ring">
                    Auswahl übernehmen
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
