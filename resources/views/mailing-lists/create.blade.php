<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Neue Mailingliste anlegen"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <form action="{{ route('mailing-lists.store') }}" method="POST">
            @csrf

            @include('mailing-lists._form')
        </form>
    </div>
</x-app-layout>
