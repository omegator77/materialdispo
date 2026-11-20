<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Gruppendetails"></x-page-header>
    </x-slot>

    <div class="page">
        <div class="card card-pad">

            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">
                        {{ $unit->bezeichnung }}
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        Materialgruppe
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('units.edit', $unit->id) }}"
                       class="btn btn-primary">
                        Bearbeiten
                    </a>

                    <form action="{{ route('units.destroy', $unit->id) }}"
                          method="POST"
                          onsubmit="return confirm('Diese Gruppe wirklich löschen? Achtung: Das kann problematisch sein, wenn noch Material zugeordnet ist.');">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="btn btn-danger">
                            Löschen
                        </button>
                    </form>

                    <a href="{{ route('units.index') }}"
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
                        <dt class="text-sm font-medium text-gray-500">Bezeichnung</dt>
                        <dd class="text-gray-900">{{ $unit->bezeichnung }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Beschreibung</dt>
                        <dd class="text-gray-900 whitespace-pre-line">
                            {{ $unit->description ?: '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

        </div>
    </div>
</x-app-layout>