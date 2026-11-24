<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Bestellungen"></x-page-header>
    </x-slot>

    <div class="page">

        @if(session('success'))
            <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('bestellungen.index') }}" class="card card-pad mb-4 flex flex-col sm:flex-row gap-3 sm:items-end">
            <div>
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-control w-full" onchange="this.form.submit()">
                    <option value="offen" @selected($status === 'offen')>Offen</option>
                    <option value="abgelegt" @selected($status === 'abgelegt')>Bezahlt &amp; abgelegt</option>
                    <option value="alle" @selected($status === 'alle')>Alle</option>
                </select>
            </div>
            <div class="flex-1">
                <label for="q" class="form-label">Suche</label>
                <input type="search" name="q" id="q" value="{{ $q }}" placeholder="Bestellt bei, was, Sendungsnummer, Besteller" class="form-control w-full">
            </div>
            <button type="submit" class="btn btn-primary">Suchen</button>
        </form>

        {{-- Desktop / Tablet --}}
        <div class="hidden md:block card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Besteller</th>
                        <th>Bestellt bei</th>
                        <th>Bestellt was</th>
                        <th>Bezahlt mit</th>
                        <th>Sendung</th>
                        <th>Stufe</th>
                        <th class="text-right">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bestellungen as $bestellung)
                        <tr>
                            <td class="whitespace-nowrap">{{ $bestellung->bestellt_am?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>{{ $bestellung->besteller?->name ?? '—' }}</td>
                            <td class="font-medium">
                                <a href="{{ route('bestellungen.show', $bestellung) }}" class="text-gray-900 hover:text-orange-500">{{ $bestellung->bestellt_bei }}</a>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($bestellung->bestellt_was, 80) }}</td>
                            <td>{{ $bestellung->bezahlt_mit }}</td>
                            <td>@include('bestellungen._sendung')</td>
                            <td>@include('bestellungen._stufe')</td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('bestellungen.show', $bestellung) }}" class="btn btn-secondary btn-sm">Details</a>
                                    @if(Auth::user()->isUser())
                                        <a href="{{ route('bestellungen.edit', $bestellung) }}" class="btn btn-primary btn-sm">Bearbeiten</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">Keine Bestellungen gefunden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Handy --}}
        <div class="md:hidden space-y-3">
            @forelse($bestellungen as $bestellung)
                <div class="card p-4">
                    <div class="flex items-start justify-between gap-2">
                        <a href="{{ route('bestellungen.show', $bestellung) }}" class="text-lg font-semibold text-gray-900 hover:text-orange-500">{{ $bestellung->bestellt_bei }}</a>
                        @include('bestellungen._stufe')
                    </div>
                    <p class="text-sm text-gray-500">{{ $bestellung->bestellt_am?->format('d.m.Y H:i') ?? '—' }} · {{ $bestellung->besteller?->name ?? '—' }}</p>
                    <div class="mt-3 space-y-1 text-sm text-gray-700">
                        <p>{{ \Illuminate\Support\Str::limit($bestellung->bestellt_was, 120) }}</p>
                        <p><span class="font-medium">Bezahlt mit:</span> {{ $bestellung->bezahlt_mit }}</p>
                        <p><span class="font-medium">Sendung:</span> @include('bestellungen._sendung')</p>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('bestellungen.show', $bestellung) }}" class="btn btn-secondary flex-1 text-center">Details</a>
                        @if(Auth::user()->isUser())
                            <a href="{{ route('bestellungen.edit', $bestellung) }}" class="btn btn-primary flex-1 text-center">Bearbeiten</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="card card-pad text-center text-gray-500">Keine Bestellungen gefunden.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $bestellungen->links() }}</div>
    </div>
</x-app-layout>
