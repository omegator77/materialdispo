<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Mieter">
            <x-slot name="actions">
                @if(Auth::user()->isUser())
            <a href="{{ route('mieter.create') }}"
               class="btn btn-primary">
                Neuer Mieter
            </a>
            @endif
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="page">

        @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
        @endif

        {{-- Desktop / Tablet --}}
        <div class="hidden md:block card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name / Firma</th>
                        <th>Kontakt</th>
                        <th>Telefon</th>
                        <th>E-Mail</th>
                        <th class="text-right">Aktionen</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($mieter as $m)
                        <tr>
                            <td class="font-medium">
                                <a href="{{ route('mieter.show', $m->id) }}"
                                   class="text-gray-900 hover:text-orange-500">
                                    {{ $m->bezeichnung }}
                                </a>
                            </td>

                            <td>
                                {{ $m->kontakt ?: '—' }}
                            </td>

                            <td>
                                {{ $m->phone ?: '—' }}
                            </td>

                            <td>
                                @if($m->email)
                                    <a href="mailto:{{ $m->email }}"
                                       class="text-orange-600 hover:underline">
                                        {{ $m->email }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('mieter.show', $m->id) }}"
                                       class="btn btn-secondary btn-sm">
                                        Details
                                    </a>

                                    @if(Auth::user()->isUser())
                                    <a href="{{ route('mieter.edit', $m->id) }}"
                                       class="btn btn-primary btn-sm">
                                        Bearbeiten
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">
                                Noch keine Mieter angelegt.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Handy --}}
        <div class="md:hidden space-y-3">

            @forelse($mieter as $m)

                <div class="card p-4">

                    <div>
                        <a href="{{ route('mieter.show', $m->id) }}"
                           class="text-lg font-semibold text-gray-900 hover:text-orange-500">
                            {{ $m->bezeichnung }}
                        </a>

                        <p class="text-sm text-gray-500">
                            {{ $m->kontakt ?: 'Kein Ansprechpartner hinterlegt' }}
                        </p>
                    </div>

                    <div class="mt-3 space-y-1 text-sm text-gray-700">
                        <p>
                            <span class="font-medium">Telefon:</span>
                            {{ $m->phone ?: '—' }}
                        </p>

                        <p>
                            <span class="font-medium">E-Mail:</span>

                            @if($m->email)
                                <a href="mailto:{{ $m->email }}"
                                   class="text-orange-600 hover:underline">
                                    {{ $m->email }}
                                </a>
                            @else
                                —
                            @endif
                        </p>
                    </div>

                    <div class="mt-4 flex gap-2">

                        <a href="{{ route('mieter.show', $m->id) }}"
                           class="btn btn-secondary flex-1 text-center">
                            Details
                        </a>

                        @if(Auth::user()->isUser())
                        <a href="{{ route('mieter.edit', $m->id) }}"
                           class="btn btn-primary flex-1 text-center">
                            Bearbeiten
                        </a>
                        @endif

                    </div>

                </div>

            @empty

                <div class="bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">
                    Noch keine Mieter angelegt.
                </div>

            @endforelse

        </div>

    </div>
</x-app-layout>
