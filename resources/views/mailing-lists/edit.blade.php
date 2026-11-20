<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Mailingliste bearbeiten"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <form action="{{ route('mailing-lists.update', $mailingList->id) }}" method="POST">
            @csrf
            @method('PUT')

            @include('mailing-lists._form')
        </form>
    </div>
</x-app-layout>
