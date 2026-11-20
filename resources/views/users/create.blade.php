<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Neuer Benutzer"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <div class="card card-pad">
            <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-control w-full">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">E-Mail</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="form-control w-full">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Rolle</label>
                    <select name="role"
                            class="form-control w-full">
                        <option value="user"   {{ old('role') === 'user'   ? 'selected' : '' }}>Benutzer</option>
                        <option value="viewer" {{ old('role') === 'viewer' ? 'selected' : '' }}>Betrachter (nur Packliste)</option>
                        <option value="admin"  {{ old('role') === 'admin'  ? 'selected' : '' }}>Admin</option>
                    </select>
                    @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Passwort</label>
                    <input type="password" name="password"
                           class="form-control w-full">
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Passwort bestätigen</label>
                    <input type="password" name="password_confirmation"
                           class="form-control w-full">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="btn btn-primary">
                        Speichern
                    </button>
                    <a href="{{ route('users.index') }}"
                       class="btn btn-secondary">
                        Abbrechen
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
