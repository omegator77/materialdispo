<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Mailinglisten">
            <x-slot name="actions">
                @if(Auth::user()->isUser())
            <a href="{{ route('mailing-lists.create') }}"
               class="btn btn-primary">
                Neue Mailingliste
            </a>
            @endif
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="page">

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        @if(Auth::user()->isAdmin())
        <div class="card p-4 mb-4">
            <h3 class="text-sm font-semibold text-gray-800 mb-2">SMTP-Test</h3>
            <form action="{{ route('test-mail.send') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="email" name="email" required
                       value="{{ old('email', Auth::user()->email) }}"
                       placeholder="empfänger@beispiel.de"
                       class="form-control w-full sm:w-80">
                <button type="submit"
                        class="btn btn-secondary whitespace-nowrap">
                    Test-Mail senden
                </button>
            </form>
        </div>
        @endif

        {{-- Desktop / Tablet --}}
        <div class="hidden md:block card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Beschreibung</th>
                        <th>Empfänger</th>
                        <th class="text-right">Aktionen</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($mailingLists as $mailingList)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $mailingList->name }}
                                @if($mailingList->is_default)
                                <span class="ml-2 badge badge-green">Standard</span>
                                @endif
                            </td>

                            <td>
                                {{ $mailingList->description ?: '—' }}
                            </td>

                            <td>
                                {{ $mailingList->recipients_count }}
                            </td>

                            <td>
                                <div class="flex justify-end gap-2">
                                    @if(Auth::user()->isUser())
                                    @unless($mailingList->is_default)
                                    <form action="{{ route('mailing-lists.makeDefault', $mailingList->id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-secondary btn-sm">
                                            Als Standard
                                        </button>
                                    </form>
                                    @endunless

                                    <a href="{{ route('mailing-lists.edit', $mailingList->id) }}"
                                       class="btn btn-primary btn-sm">
                                        Bearbeiten
                                    </a>

                                    <form action="{{ route('mailing-lists.destroy', $mailingList->id) }}" method="POST"
                                          onsubmit="return confirm('Mailingliste wirklich löschen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-danger btn-sm">
                                            Löschen
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-state">
                                Noch keine Mailinglisten angelegt.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Handy --}}
        <div class="md:hidden space-y-3">

            @forelse($mailingLists as $mailingList)

                <div class="card p-4">

                    <div>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $mailingList->name }}
                            @if($mailingList->is_default)
                            <span class="ml-2 badge badge-green">Standard</span>
                            @endif
                        </p>

                        <p class="text-sm text-gray-500">
                            {{ $mailingList->description ?: 'Keine Beschreibung' }}
                        </p>
                    </div>

                    <p class="mt-3 text-sm text-gray-700">
                        <span class="font-medium">Empfänger:</span> {{ $mailingList->recipients_count }}
                    </p>

                    @if(Auth::user()->isUser())
                    <div class="mt-4 flex flex-wrap gap-2">
                        @unless($mailingList->is_default)
                        <form action="{{ route('mailing-lists.makeDefault', $mailingList->id) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="btn btn-secondary w-full text-center">
                                Als Standard
                            </button>
                        </form>
                        @endunless

                        <a href="{{ route('mailing-lists.edit', $mailingList->id) }}"
                           class="btn btn-primary flex-1 text-center">
                            Bearbeiten
                        </a>

                        <form action="{{ route('mailing-lists.destroy', $mailingList->id) }}" method="POST"
                              class="flex-1" onsubmit="return confirm('Mailingliste wirklich löschen?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn btn-danger w-full text-center">
                                Löschen
                            </button>
                        </form>
                    </div>
                    @endif

                </div>

            @empty

                <div class="card card-pad text-center text-gray-500">
                    Noch keine Mailinglisten angelegt.
                </div>

            @endforelse

        </div>

    </div>
</x-app-layout>
