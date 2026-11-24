<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Bestellung"></x-page-header>
    </x-slot>

    @php
        $user = Auth::user();
        $stufe = $bestellung->stufe();
        $kannZuruecknehmen = $user->isUser()
            && ($stufe === 'abgelegt' ? $user->ist_buchhaltung : $bestellung->rechnung_vorhanden_at !== null);
    @endphp

    <div class="page">
        @if(session('success'))
            <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <div class="card card-pad">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">{{ $bestellung->bestellt_bei }}</h3>
                    <p class="mt-1">@include('bestellungen._stufe')</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if($user->isUser())
                        <a href="{{ route('bestellungen.edit', $bestellung) }}" class="btn btn-primary">Bearbeiten</a>

                        @if(! $bestellung->rechnung_vorhanden_at && $stufe !== 'abgelegt')
                            <form action="{{ route('bestellungen.rechnung', $bestellung) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary">Rechnung liegt vor</button>
                            </form>
                        @endif

                        @if($user->ist_buchhaltung && $stufe === 'rechnung')
                            <form action="{{ route('bestellungen.abgelegt', $bestellung) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary">Bezahlt &amp; abgelegt</button>
                            </form>
                        @endif

                        @if($kannZuruecknehmen)
                            <form action="{{ route('bestellungen.zuruecknehmen', $bestellung) }}" method="POST"
                                  onsubmit="return confirm('Die letzte Stufe wirklich zurücknehmen?');">
                                @csrf
                                <button type="submit" class="btn btn-secondary">Stufe zurücknehmen</button>
                            </form>
                        @endif

                        <form action="{{ route('bestellungen.resync', $bestellung) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-secondary">In Slack neu senden</button>
                        </form>
                    @endif

                    <a href="{{ route('bestellungen.index') }}" class="btn btn-secondary">Zurück</a>
                </div>
            </div>

            <section class="border-t pt-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Bestelldaten</h4>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Bestellt bei</dt>
                        <dd class="text-gray-900">{{ $bestellung->bestellt_bei }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Datum</dt>
                        <dd class="text-gray-900">{{ $bestellung->bestellt_am?->format('d.m.Y H:i') ?? '—' }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Bestellt was</dt>
                        <dd class="text-gray-900 whitespace-pre-line">{{ $bestellung->bestellt_was }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Bezahlt mit</dt>
                        <dd class="text-gray-900">{{ $bestellung->bezahlt_mit }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Besteller</dt>
                        <dd class="text-gray-900">{{ $bestellung->besteller?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Sendung</dt>
                        <dd class="text-gray-900">@include('bestellungen._sendung')</dd>
                    </div>
                </dl>
            </section>

            <section class="border-t pt-6 mt-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Stufenverlauf</h4>
                <ol class="space-y-2 text-sm text-gray-700">
                    <li><span class="badge badge-gray">Bestellt</span>
                        {{ $bestellung->bestellt_am?->format('d.m.Y H:i') ?? '—' }}
                        @if($bestellung->besteller) · {{ $bestellung->besteller->name }} @endif
                    </li>
                    @if($bestellung->sendungsnummer)
                        <li><span class="badge badge-blue">Unterwegs</span> Sendungsnummer {{ $bestellung->sendungsnummer }}</li>
                    @endif
                    @if($bestellung->rechnung_vorhanden_at)
                        <li><span class="badge badge-amber">Rechnung liegt vor</span>
                            {{ $bestellung->rechnung_vorhanden_at->format('d.m.Y H:i') }}
                            @if($bestellung->rechnungVon) · {{ $bestellung->rechnungVon->name }} @endif
                        </li>
                    @endif
                    @if($bestellung->abgelegt_at)
                        <li><span class="badge badge-green">Bezahlt &amp; abgelegt</span>
                            {{ $bestellung->abgelegt_at->format('d.m.Y H:i') }}
                            @if($bestellung->abgelegtVon) · {{ $bestellung->abgelegtVon->name }} @endif
                        </li>
                    @endif
                </ol>
            </section>
        </div>
    </div>
</x-app-layout>
