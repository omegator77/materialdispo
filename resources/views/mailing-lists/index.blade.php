<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mailinglisten
            </h2>

            @if(Auth::user()->isUser())
            <a href="{{ route('mailing-lists.create') }}"
               class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                Neue Mailingliste
            </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl w-11/12 mx-auto mt-6">

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
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4 mb-4">
            <h3 class="text-sm font-semibold text-gray-800 mb-2">SMTP-Test</h3>
            <form action="{{ route('test-mail.send') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="email" name="email" required
                       value="{{ old('email', Auth::user()->email) }}"
                       placeholder="empfänger@beispiel.de"
                       class="form-control w-full sm:w-80">
                <button type="submit"
                        class="inline-flex justify-center bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2 px-4 rounded whitespace-nowrap">
                    Test-Mail senden
                </button>
            </form>
        </div>
        @endif

        {{-- Desktop / Tablet --}}
        <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="text-left px-4 py-3">Name</th>
                        <th class="text-left px-4 py-3">Beschreibung</th>
                        <th class="text-left px-4 py-3">Empfänger</th>
                        <th class="text-right px-4 py-3">Aktionen</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($mailingLists as $mailingList)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $mailingList->name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $mailingList->description ?: '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $mailingList->recipients_count }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @if(Auth::user()->isUser())
                                    <a href="{{ route('mailing-lists.edit', $mailingList->id) }}"
                                       class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-1 px-3 rounded">
                                        Bearbeiten
                                    </a>

                                    <form action="{{ route('mailing-lists.destroy', $mailingList->id) }}" method="POST"
                                          onsubmit="return confirm('Mailingliste wirklich löschen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1 px-3 rounded">
                                            Löschen
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-6 text-gray-500">
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

                <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">

                    <div>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $mailingList->name }}
                        </p>

                        <p class="text-sm text-gray-500">
                            {{ $mailingList->description ?: 'Keine Beschreibung' }}
                        </p>
                    </div>

                    <p class="mt-3 text-sm text-gray-700">
                        <span class="font-medium">Empfänger:</span> {{ $mailingList->recipients_count }}
                    </p>

                    @if(Auth::user()->isUser())
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('mailing-lists.edit', $mailingList->id) }}"
                           class="flex-1 text-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-3 rounded">
                            Bearbeiten
                        </a>

                        <form action="{{ route('mailing-lists.destroy', $mailingList->id) }}" method="POST"
                              class="flex-1" onsubmit="return confirm('Mailingliste wirklich löschen?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded">
                                Löschen
                            </button>
                        </form>
                    </div>
                    @endif

                </div>

            @empty

                <div class="bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">
                    Noch keine Mailinglisten angelegt.
                </div>

            @endforelse

        </div>

    </div>
</x-app-layout>
