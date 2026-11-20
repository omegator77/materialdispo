<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Packliste aus VB-Protokoll" :subtitle="$production->bezeichnung">
            <x-slot name="actions">
                <a href="{{ route('productions.show', $production) }}" class="btn btn-secondary">Abbrechen</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    @php
        $label = fn ($i, array $warnings = []) => $i->bezeichnung.($i->nummer ? ' ('.$i->nummer.')' : '')
            .(isset($warnings[$i->id]) ? ' (Miete, '.$warnings[$i->id].')' : '');
        $rigs = $proposal['rigs'];
        $needs = $proposal['needs'];
        $canApply = (bool) $needs || collect($rigs)->contains(fn ($r) => $r['takeable']);
    @endphp

    <div class="page">
        <x-production-steps :production="$production" active="packliste" />

        @if($errors->any())
            <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if($proposal['empty'])
            <div class="card card-pad text-center text-gray-500">
                Alles gedeckt – aus dem VB-Protokoll gibt es nichts Offenes zu übernehmen.
                <div class="mt-4">
                    <a href="{{ route('productions.show', $production) }}" class="btn btn-secondary">Zur Packliste</a>
                </div>
            </div>
        @else
            <form action="{{ route('productions.vbVorschlag.apply', $production) }}" method="POST" class="space-y-6">
                @csrf

                @if($rigs)
                    <div class="space-y-4">
                        <h2 class="card-title">Kamerazüge</h2>
                        @foreach($rigs as $i => $rig)
                            <div class="card card-pad">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                    <div class="font-semibold text-gray-800">
                                        {{ $rig['cam_label'] }}
                                        @if($rig['no_type'])
                                            <span class="badge badge-amber">Kamera ohne Typ im VB-Protokoll</span>
                                        @elseif($rig['camera_missing'])
                                            <span class="badge badge-amber">keine Kamera frei</span>
                                        @endif
                                    </div>
                                    @if($rig['takeable'])
                                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" name="rigs[{{ $i }}][take]" value="1" checked class="form-check">
                                            Zug übernehmen
                                        </label>
                                    @endif
                                </div>

                                @if($rig['takeable'])
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @foreach($rig['slots'] as $slot => $data)
                                            @php $selectable = $data['item'] ? collect([$data['item']])->merge($data['alternatives']) : $data['alternatives']; @endphp
                                            <div class="min-w-0">
                                                <label class="form-label" for="rig-{{ $i }}-{{ $slot }}">
                                                    {{ $data['label'] }}
                                                    @if($data['typ'])
                                                        <span class="text-gray-400 font-normal">· {{ $data['typ']->bezeichnung }}</span>
                                                    @endif
                                                </label>
                                                <select id="rig-{{ $i }}-{{ $slot }}" name="rigs[{{ $i }}][{{ $slot }}]" class="form-control w-full">
                                                    <option value="">— leer —</option>
                                                    @foreach($selectable as $opt)
                                                        <option value="{{ $opt->id }}" @selected($data['item'] && $data['item']->id === $opt->id)>{{ $label($opt, $data['warnings']) }}</option>
                                                    @endforeach
                                                </select>
                                                @if($data['gap'])
                                                    <span class="badge badge-amber mt-1">keins frei</span>
                                                @endif
                                                @if($data['warning'])
                                                    <span class="badge badge-blue mt-1">{{ $data['warning'] }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500">
                                        Dieser Zug kann nicht vorgeschlagen werden und wird nicht übernommen.
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($needs)
                    <div class="card card-pad space-y-4">
                        <h2 class="card-title">Übriges Material</h2>
                        @foreach($needs as $need)
                            <div>
                                <div class="font-medium text-gray-800">
                                    {{ $need['label'] }}
                                    <span class="text-gray-400 font-normal">· benötigt {{ $need['requested'] }}</span>
                                    @if($need['missing'] > 0)
                                        <span class="badge badge-amber">keins frei: {{ $need['missing'] }} fehlen</span>
                                    @endif
                                </div>
                                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($need['items'] as $chosen)
                                        <select name="items[]" class="form-control w-full" aria-label="{{ $need['label'] }}">
                                            <option value="">— weglassen —</option>
                                            @foreach(collect($need['items'])->reject(fn ($o) => $o->id !== $chosen->id)->merge($need['alternatives']) as $opt)
                                                <option value="{{ $opt->id }}" @selected($opt->id === $chosen->id)>{{ $label($opt, $need['warnings']) }}</option>
                                            @endforeach
                                        </select>
                                    @endforeach
                                </div>
                                @foreach($need['warnings'] as $warn)
                                    @if($loop->first)
                                        <span class="badge badge-blue mt-2">{{ $warn }}</span>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
                    <a href="{{ route('productions.show', $production) }}" class="btn btn-secondary">Abbrechen</a>
                    @if($canApply)
                        <button type="submit" class="btn btn-primary">Übernehmen</button>
                    @else
                        <span class="text-sm text-gray-500">Nichts zum Übernehmen.</span>
                    @endif
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
