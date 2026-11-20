<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Gerätetyp bearbeiten">
            <x-slot name="actions">
                <a href="{{ route('geraetetypen.index') }}"
                class="btn btn-secondary">
                Zurück
            </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="page-narrow">
        @include('geraetetypen._form')
    </div>
</x-app-layout>
