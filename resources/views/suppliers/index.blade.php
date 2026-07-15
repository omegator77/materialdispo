<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Vermieter
            </h2>

            @if(Auth::user()->isUser())
            <a href="{{ route('suppliers.create') }}"
               class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                Neuer Vermieter
            </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl w-11/12 mx-auto mt-6">

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
        <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="text-left px-4 py-3">Name / Firma</th>
                        <th class="text-left px-4 py-3">Kontakt</th>
                        <th class="text-left px-4 py-3">Telefon</th>
                        <th class="text-left px-4 py-3">E-Mail</th>
                        <th class="text-right px-4 py-3">Aktionen</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($suppliers as $supplier)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">
                                <a href="{{ route('suppliers.show', $supplier->id) }}"
                                   class="text-gray-900 hover:text-orange-500">
                                    {{ $supplier->bezeichnung }}
                                </a>
                            </td>

                            <td class="px-4 py-3">
                                {{ $supplier->kontakt ?: '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $supplier->phone ?: '—' }}
                            </td>

                            <td class="px-4 py-3">
                                @if($supplier->email)
                                    <a href="mailto:{{ $supplier->email }}"
                                       class="text-orange-600 hover:underline">
                                        {{ $supplier->email }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('suppliers.show', $supplier->id) }}"
                                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1 px-3 rounded">
                                        Details
                                    </a>

                                    @if(Auth::user()->isUser())
                                    <a href="{{ route('suppliers.edit', $supplier->id) }}"
                                       class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-1 px-3 rounded">
                                        Bearbeiten
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-6 text-gray-500">
                                Noch keine Vermieter angelegt.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Handy --}}
        <div class="md:hidden space-y-3">

            @forelse($suppliers as $supplier)

                <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">

                    <div>
                        <a href="{{ route('suppliers.show', $supplier->id) }}"
                           class="text-lg font-semibold text-gray-900 hover:text-orange-500">
                            {{ $supplier->bezeichnung }}
                        </a>

                        <p class="text-sm text-gray-500">
                            {{ $supplier->kontakt ?: 'Kein Ansprechpartner hinterlegt' }}
                        </p>
                    </div>

                    <div class="mt-3 space-y-1 text-sm text-gray-700">
                        <p>
                            <span class="font-medium">Telefon:</span>
                            {{ $supplier->phone ?: '—' }}
                        </p>

                        <p>
                            <span class="font-medium">E-Mail:</span>

                            @if($supplier->email)
                                <a href="mailto:{{ $supplier->email }}"
                                   class="text-orange-600 hover:underline">
                                    {{ $supplier->email }}
                                </a>
                            @else
                                —
                            @endif
                        </p>
                    </div>

                    <div class="mt-4 flex gap-2">

                        <a href="{{ route('suppliers.show', $supplier->id) }}"
                           class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded">
                            Details
                        </a>

                        @if(Auth::user()->isUser())
                        <a href="{{ route('suppliers.edit', $supplier->id) }}"
                           class="flex-1 text-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-3 rounded">
                            Bearbeiten
                        </a>
                        @endif

                    </div>

                </div>

            @empty

                <div class="bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">
                    Noch keine Vermieter angelegt.
                </div>

            @endforelse

        </div>

    </div>
</x-app-layout>