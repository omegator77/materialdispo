<x-app-layout>
<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Productions') }}
    </h2>
</x-slot>

<div class="max-w-7xl w-4/5 mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">
    <div class="flex flex-wrap md:flex-nowrap gap-8">
        <div class="w-full md:w-1/2">
            <h1 class="font-bold text-2xl mb-4">Produktion: {{ $production->bezeichnung }}</h1>
            <p class="font-bold mb-4">Buchungszeitraum: {{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '/' }} bis {{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '/' }}</p>

            <h2 class="text-xl font-semibold mt-8 mb-4">Verfügbare Items hinzufügen</h2>
            <!-- Gruppenauswahl -->
            <form method="GET" action="{{ route('productions.show', $production->id) }}" class="mb-4">
                <label for="unit" class="block font-semibold mb-2">Gruppe filtern:</label>
                <select name="unit" id="unit" class="w-full p-2 border border-gray-300 rounded mb-4" onchange="this.form.submit()">
                    <option value="">Alle Gruppen</option>
                    @foreach ($allUnits as $unit)
                        <option value="{{ $unit->id }}" {{ request('unit') == $unit->id ? 'selected' : '' }}>{{ $unit->bezeichnung }}</option>
                    @endforeach
                </select>
            </form>

            <!-- Item-Auswahl -->
            <form action="{{ route('productions.attachItem', $production->id) }}" method="POST" class="mb-8">
                @csrf
                <input type="hidden" name="unit" value="{{ request('unit') }}">
                <label for="item_id" class="block font-semibold mb-2">Material auswählen:</label>
                <select name="item_id" id="item_id" class="w-full p-2 border border-gray-300 rounded mb-4">
                    @foreach ($availableItems as $item)
                        <option value="{{ $item->id }}">{{ $item->bezeichnung }} {{ $item->nummer }} ({{ $item->unit->bezeichnung }})</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-bold">
                    Hinzufügen
                </button>
            </form>
        </div>

        <div class="w-full md:w-1/2">
            <h2 class="text-xl font-semibold mb-4">Gepacktes Material</h2>
            <ul class="list-disc pl-5 space-y-3">
                @foreach ($production->items as $item)
                    <li class="flex items-center">
                        <span class="flex-1">{{ $item->bezeichnung }} {{ $item->nummer }} ({{ $item->unit->bezeichnung }})</span>
                        <form action="{{ route('productions.detachItem', [$production->id, $item->id]) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-bold">
                                Entfernen
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

@if (session('success'))
    <div id="success-message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mt-4 fixed top-4 right-4 z-50">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div id="error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mt-4 fixed top-4 right-4 z-50">
        {{ session('error') }}
    </div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.transition = 'opacity 0.5s ease';
                successMessage.style.opacity = '0';
                setTimeout(() => successMessage.remove(), 500);
            }, 3000);
        }
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.transition = 'opacity 0.5s ease';
                errorMessage.style.opacity = '0';
                setTimeout(() => errorMessage.remove(), 500);
            }, 3000);
        }
    });
</script>

</x-app-layout>
