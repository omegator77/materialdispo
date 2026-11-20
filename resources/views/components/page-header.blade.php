{{--
    Einheitlicher Seitenkopf: Titel links, Aktionen rechts. Wird im Header-Slot des Layouts verwendet:
    <x-slot name="header"><x-page-header title="Geräte"><x-slot name="actions">…</x-slot></x-page-header></x-slot>
--}}
@props(['title', 'subtitle' => null])

<div data-page-header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <h1 class="truncate text-xl font-semibold leading-tight text-gray-900">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
