<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Profil"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <div class="card card-pad">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card card-pad">
            @include('profile.partials.update-password-form')
        </div>

        <div class="card card-pad">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
