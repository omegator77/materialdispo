
<x-app-layout>
<body>
    <h1>Unit Details</h1>

    <p><strong>ID:</strong> {{ $unit->id }}</p>
    <p><strong>Bezeichnung:</strong> {{ $unit->bezeichnung }}</p>
    <p><strong>Beschreibung:</strong> {{ $unit->description }}</p>

    <a href="{{ route('units.index') }}">Zurück zur Übersicht</a>

    </body>
</x-app-layout>