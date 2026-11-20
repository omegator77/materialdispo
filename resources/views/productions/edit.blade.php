<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Produktion bearbeiten"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <div class="card card-pad">
            <form action="{{ route('productions.update', $production->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label for="bezeichnung" class="form-label">Bezeichnung</label>
                        <input type="text" name="bezeichnung" id="bezeichnung"
                               class="form-control w-full"
                               value="{{ old('bezeichnung', $production->bezeichnung) }}">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="booking_start" class="form-label">Produktionsbeginn</label>
                            <input type="text" name="booking_start" id="booking_start"
                                   class="form-control w-full datepicker"
                                   value="{{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '' }}">
                        </div>
                        <div>
                            <label for="booking_end" class="form-label">Produktionsende</label>
                            <input type="text" name="booking_end" id="booking_end"
                                   class="form-control w-full datepicker"
                                   value="{{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '' }}">
                        </div>
                    </div>

                    <div>
                        <label for="packlist_notes" class="form-label">Packlisten-Notiz</label>
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
                                class="btn btn-primary focus:outline-none focus:ring">
                            Speichern
                        </button>
                    </div>
                </div>
            </form>

            @if(Auth::user()->isUser())
            <div class="mt-6 pt-6 border-t border-gray-200 flex justify-end">
                <form action="{{ route('productions.destroy', $production->id) }}"
                      method="POST"
                      onsubmit="return confirm('Diese Produktion wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.');">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                            class="btn btn-danger focus:outline-none focus:ring">
                        Produktion löschen
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

    @include('productions._table')
</x-app-layout>
