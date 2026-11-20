<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Mieterdetails"></x-page-header>
    </x-slot>

    <div class="page">
        <div class="card card-pad">

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
                       class="btn btn-primary">
                        Bearbeiten
                    </a>

                    <form action="{{ route('mieter.destroy', $mieter->id) }}"
                          method="POST"
                          onsubmit="return confirm('Diesen Mieter wirklich löschen? Achtung: Zugeordnetes Material könnte betroffen sein.');">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="btn btn-danger">
                            Löschen
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('mieter.index') }}"
                       class="btn btn-secondary">
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
                                <a href="mailto:{{ $mieter->email }}" class="link">
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
