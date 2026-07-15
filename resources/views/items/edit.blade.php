<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Material bearbeiten
        </h2>
    </x-slot>

    <div class="max-w-5xl w-11/12 mx-auto mt-6">
        <form action="{{ route('items.update', $item->id) }}" method="POST">
            @csrf
            @method('PUT')

            @include('items._form')
        </form>

        {{-- Detach-Formulare bewusst AUSSERHALB des Bearbeiten-Formulars (top-level).
             Die „Entfernen"-Buttons in _form.blade.php verweisen per form-Attribut hierher.
             Verschachtelte <form>-Elemente sind ungültiges HTML und haben zuvor dazu
             geführt, dass ihr _method=DELETE beim Speichern mitgesendet wurde und das
             Gerät gelöscht statt aktualisiert wurde. --}}
        @foreach($item->mietvorgaenge as $mietvorgang)
            <form id="detach-miet-{{ $mietvorgang->id }}"
                  action="{{ route('mietvorgaenge.detachItem', [$mietvorgang, $item]) }}"
                  method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
        @foreach($item->vermietvorgaenge as $vermietvorgang)
            <form id="detach-vermiet-{{ $vermietvorgang->id }}"
                  action="{{ route('vermietvorgaenge.detachItem', [$vermietvorgang, $item]) }}"
                  method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    </div>
</x-app-layout>