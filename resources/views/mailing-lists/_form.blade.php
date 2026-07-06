<div class="bg-white p-6 border border-gray-300 rounded-lg shadow-md space-y-6">

    @if ($errors->any())
        <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            Mailingliste
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name', $mailingList->name ?? '') }}"
                    class="form-control w-full"
                    required
                >
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Beschreibung</label>
                <input
                    type="text"
                    name="description"
                    id="description"
                    value="{{ old('description', $mailingList->description ?? '') }}"
                    class="form-control w-full"
                >
            </div>
        </div>
    </section>

    <section
        class="border-t pt-6"
        x-data="{
            recipients: @js(
                old('recipient_email')
                    ? collect(old('recipient_email', []))->map(fn ($email, $i) => ['name' => old('recipient_name')[$i] ?? '', 'email' => $email])->values()->all()
                    : (($mailingList->recipients ?? collect())->map(fn ($r) => ['name' => $r->name, 'email' => $r->email])->values()->all() ?: [['name' => '', 'email' => '']])
            )
        }"
    >
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Empfänger</h3>

        <template x-for="(recipient, index) in recipients" :key="index">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-2 items-center">
                <input type="text" :name="'recipient_name[' + index + ']'" x-model="recipient.name"
                       placeholder="Name (optional)" class="form-control md:col-span-2">
                <input type="email" :name="'recipient_email[' + index + ']'" x-model="recipient.email"
                       placeholder="email@example.com" class="form-control md:col-span-2" required>
                <button type="button" @click="recipients.splice(index, 1)"
                        class="text-red-600 hover:underline text-sm text-left">Entfernen</button>
            </div>
        </template>

        <button type="button" @click="recipients.push({name: '', email: ''})"
                class="text-blue-600 hover:underline text-sm mt-2">+ Empfänger hinzufügen</button>
    </section>

    <div class="border-t pt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
        <a href="{{ route('mailing-lists.index') }}"
           class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
            Abbrechen
        </a>

        <button type="submit"
                class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
            Speichern
        </button>
    </div>
</div>
