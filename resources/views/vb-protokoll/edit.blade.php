<x-app-layout>
    <x-slot name="header">
        <x-page-header title="VB-Protokoll bearbeiten">
            <x-slot name="actions">
                <a href="{{ route('vb-protokoll.show', $production->id) }}"
                class="btn btn-secondary">
                Zurück
            </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="page">
        <x-production-steps :production="$production" active="vb" />

        @include('vb-protokoll._form')
    </div>
</x-app-layout>
