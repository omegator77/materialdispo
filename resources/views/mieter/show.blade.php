<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mieterdetails
        </h2>
    </x-slot>

    <div class="max-w-5xl w-11/12 mx-auto mt-6 space-y-6">
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">

            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">
                        {{ $mieter->bezeichnung }}
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        Mieter / Kunde
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if(Auth::user()->isUser())
                    <a href="{{ route('mieter.edit', $mieter->id) }}"
                       class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                        Bearbeiten
                    </a>

                    <form action="{{ route('mieter.destroy', $mieter->id) }}"
                          method="POST"
                          onsubmit="return confirm('Diesen Mieter wirklich löschen? Achtung: Zugeordnetes Material könnte betroffen sein.');">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                            Löschen
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('mieter.index') }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                        Zurück
                    </a>
                </div>
            </div>

            <section class="border-t pt-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                    Stammdaten
                </h4>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name / Firma</dt>
                        <dd class="text-gray-900">{{ $mieter->bezeichnung }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Kontaktperson</dt>
                        <dd class="text-gray-900">{{ $mieter->kontakt ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Telefon</dt>
                        <dd class="text-gray-900">{{ $mieter->phone ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">E-Mail</dt>
                        <dd class="text-gray-900">
                            @if($mieter->email)
                                <a href="mailto:{{ $mieter->email }}" class="text-orange-600 hover:underline">
                                    {{ $mieter->email }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

        </div>
    </div>
</x-app-layout>
