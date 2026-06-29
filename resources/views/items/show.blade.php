<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Materialdetails
                </h2>
            </div>

            <div class="flex gap-2">
                @if(Auth::user()->isUser())
                <a href="{{ route('items.edit', $item->id) }}"
                    class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                    Bearbeiten
                </a>
                @endif

                <a href="{{ route('items.index') }}"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                    Zurück
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl w-11/12 mx-auto mt-6">
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6 space-y-6">

            <div>
                <h3 class="text-2xl font-bold text-gray-900">
                    {{ $item->bezeichnung }}
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    {{ $item->unit->bezeichnung ?? 'Keine Gruppe' }}
                </p>
            </div>

            <section class="border-t pt-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                    Stammdaten
                </h4>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nummer</dt>
                        <dd class="text-gray-900">{{ $item->nummer ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Gruppe</dt>
                        <dd class="text-gray-900">{{ $item->unit->bezeichnung ?? '—' }}</dd>
                    </div>

                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Bemerkung</dt>
                        <dd class="text-gray-900 whitespace-pre-line">
                            {{ $item->description ?: '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="border-t pt-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                    Mietstatus
                </h4>

                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="text-gray-900">
                            {{ $item->suppliers_id ? 'Mietmaterial' : 'Eigenmaterial' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Vermieter</dt>
                        <dd class="text-gray-900">
                            {{ $item->supplier->bezeichnung ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Mietzeitraum</dt>
                        <dd class="text-gray-900">
                            @if($item->suppliers_id)
                            {{ $item->rent_start ? \Carbon\Carbon::parse($item->rent_start)->format('d.m.Y') : '—' }}
                            –
                            {{ $item->rent_end ? \Carbon\Carbon::parse($item->rent_end)->format('d.m.Y') : '—' }}
                            @else
                            —
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            @if($item->cameraDetail)
            <section class="border-t pt-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                    Kamera-Details
                </h4>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Body Seriennummer</dt>
                        <dd class="text-gray-900">{{ $item->cameraDetail->body_serial ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Fiber Adapter Seriennummer</dt>
                        <dd class="text-gray-900">{{ $item->cameraDetail->fiber_adapter_serial ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Großer Sucher</dt>
                        <dd class="text-gray-900">
                            {{ $item->cameraDetail->large_viewfinder_type ?: '—' }}
                            @if($item->cameraDetail->large_viewfinder_serial)
                            <span class="text-gray-500">
                                · {{ $item->cameraDetail->large_viewfinder_serial }}
                            </span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Kleiner Sucher</dt>
                        <dd class="text-gray-900">
                            {{ $item->cameraDetail->small_viewfinder_type ?: '—' }}
                            @if($item->cameraDetail->small_viewfinder_serial)
                            <span class="text-gray-500">
                                · {{ $item->cameraDetail->small_viewfinder_serial }}
                            </span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">SSL Lizenz</dt>
                        <dd class="text-gray-900">
                            {{ $item->cameraDetail->ssl_license ? 'Ja' : 'Nein' }}
                        </dd>
                    </div>
                </dl>
            </section>
            @endif

            @if($item->monitorDetail)

            <section class="border-t pt-6 mt-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                    Monitor-Details
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <span class="font-semibold">Hersteller:</span>
                        {{ $item->monitorDetail->manufacturer ?: '—' }}
                    </div>

                    <div>
                        <span class="font-semibold">Modell:</span>
                        {{ $item->monitorDetail->model ?: '—' }}
                    </div>

                    <div>
                        <span class="font-semibold">Seriennummer:</span>
                        {{ $item->monitorDetail->serial_number ?: '—' }}
                    </div>

                    <div>
                        <span class="font-semibold">Bildschirmgröße:</span>
                        {{ $item->monitorDetail->screen_size ?: '—' }}
                    </div>

                    <div>
                        <span class="font-semibold">Max. Eingabeformat:</span>
                        {{ $item->monitorDetail->max_input_format ?: '—' }}
                    </div>

                    <div>
                        <span class="font-semibold">Standfuß:</span>
                        @if($item->monitorDetail->has_stand)
                        Ja
                        @if($item->monitorDetail->stand_number)
                        (Nr. {{ $item->monitorDetail->stand_number }})
                        @endif
                        @else
                        Nein
                        @endif
                    </div>

                    <div>
                        <span class="font-semibold">Lautsprecher:</span>
                        {{ $item->monitorDetail->has_speakers ? 'Ja' : 'Nein' }}
                    </div>

                    <div>
                        <span class="font-semibold">Kopfhörer:</span>
                        {{ $item->monitorDetail->has_headphone ? 'Ja' : 'Nein' }}
                    </div>

                    <div>
                        <span class="font-semibold">Wandler Nr.:</span>
                        {{ $item->monitorDetail->converter_number ?: '—' }}
                    </div>

                    <div>
                        <span class="font-semibold">Wandler:</span>
                        {{ $item->monitorDetail->converter_model ?: '—' }}
                    </div>

                    <div>
                        <span class="font-semibold">Wandler Audio:</span>
                        {{ $item->monitorDetail->converter_audio ? 'Ja' : 'Nein' }}
                    </div>

                </div>
            </section>
            @endif

            @if($item->lensDetail)

            <section class="border-t pt-6 mt-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                    Objektiv-Details
                </h4>

                <div class="card-body">
                    <p><strong>Hersteller:</strong> {{ $item->lensDetail->manufacturer ?? '-' }}</p>
                    <p><strong>Modell:</strong> {{ $item->lensDetail->model ?? '-' }}</p>
                    <p><strong>Zoomfaktor:</strong> {{ $item->lensDetail->zoom_factor ?? '-' }}</p>
                    <p><strong>Seriennummer:</strong> {{ $item->lensDetail->serial_number ?? '-' }}</p>

                    <hr>

                    <h5>Zoomgriff</h5>
                    <p><strong>Typ:</strong> {{ $item->lensDetail->zoom_servo_model ?? '-' }}</p>
                    <p><strong>Seriennummer:</strong> {{ $item->lensDetail->zoom_servo_serial_number ?? '-' }}</p>

                    <hr>

                    <h5>Schärfegriff</h5>
                    <p><strong>Typ:</strong> {{ $item->lensDetail->focus_servo_model ?? '-' }}</p>
                    <p><strong>Seriennummer:</strong> {{ $item->lensDetail->focus_servo_serial_number ?? '-' }}</p>
                </div>
            </div>
            @endif

            <div class="border-t pt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
                <a href="{{ route('items.index') }}"
                    class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                    Zurück
                </a>

                @if(Auth::user()->isUser())
                <a href="{{ route('items.edit', $item->id) }}"
                    class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                    Bearbeiten
                </a>

                <form action="{{ route('items.destroy', $item->id) }}"
                    method="POST"
                    onsubmit="return confirm('Dieses Material wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.');">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                        class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                        Löschen
                    </button>
                </form>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>