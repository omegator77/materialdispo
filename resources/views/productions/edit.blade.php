<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Produktion bearbeiten
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto mt-6 px-4">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
            <form action="{{ route('productions.update', $production->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label for="bezeichnung" class="block text-sm font-medium text-gray-700 mb-1">Bezeichnung</label>
                        <input type="text" name="bezeichnung" id="bezeichnung"
                               class="form-control w-full"
                               value="{{ old('bezeichnung', $production->bezeichnung) }}">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="booking_start" class="block text-sm font-medium text-gray-700 mb-1">Produktionsbeginn</label>
                            <input type="text" name="booking_start" id="booking_start"
                                   class="form-control w-full datepicker"
                                   value="{{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '' }}">
                        </div>
                        <div>
                            <label for="booking_end" class="block text-sm font-medium text-gray-700 mb-1">Produktionsende</label>
                            <input type="text" name="booking_end" id="booking_end"
                                   class="form-control w-full datepicker"
                                   value="{{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '' }}">
                        </div>
                    </div>

                    <div>
                        <label for="packlist_notes" class="block text-sm font-medium text-gray-700 mb-1">Packlisten-Notiz</label>
                        <textarea name="packlist_notes" id="packlist_notes" rows="4"
                                  class="form-control w-full"
                                  placeholder="Hinweise für die gesamte Packliste...">{{ old('packlist_notes', $production->packlist_notes ?? '') }}</textarea>
                    </div>

                    @if($errors->any())
                        <ul class="text-sm text-red-600 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                                class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-6 rounded focus:outline-none focus:ring">
                            Speichern
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('productions._table')
</x-app-layout>
