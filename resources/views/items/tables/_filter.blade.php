<div class="max-w-7xl w-11/12 mx-auto mt-6">
    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">
        <form method="GET" action="{{ route('items.index') }}">
            <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                <div class="w-full sm:w-72">
                    <label for="unitFilter" class="block text-sm font-medium text-gray-700 mb-1">
                        Gruppe filtern
                    </label>
                    <select id="unitFilter" name="unit_id" class="form-control w-full" onchange="this.form.submit()">
                        <option value="">Alle Gruppen</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ (request('unit_id') ?? '') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->bezeichnung }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if(request('unit_id'))
                    <a href="{{ route('items.index') }}"
                       class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                        Filter zurücksetzen
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>
