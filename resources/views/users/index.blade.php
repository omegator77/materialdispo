<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Benutzerverwaltung">
            <x-slot name="actions">
                <a href="{{ route('users.create') }}"
               class="btn btn-primary">
                Neuer Benutzer
            </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="page">

        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-300 text-green-800 rounded px-4 py-3">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-300 text-red-800 rounded px-4 py-3">{{ session('error') }}</div>
        @endif

        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Rolle</th>
                        <th class="text-right">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr class="border-b hover:bg-gray-50 {{ $user->id === auth()->id() ? 'bg-blue-50' : '' }}">
                        <td class="font-medium">
                            {{ $user->name }}
                            @if($user->id === auth()->id())
                                <span class="link ml-1 text-xs">(du)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td>
                            @php
                                $badge = match($user->role) {
                                    'admin'  => ['bg-red-100 text-red-700', 'Admin'],
                                    'user'   => ['bg-blue-100 text-blue-700', 'Benutzer'],
                                    'viewer' => ['bg-gray-100 text-gray-600', 'Betrachter'],
                                    default  => ['bg-gray-100 text-gray-600', $user->role],
                                };
                            @endphp
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $badge[0] }}">
                                {{ $badge[1] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('users.edit', $user) }}"
                                   class="btn btn-primary btn-sm">
                                    Bearbeiten
                                </a>
                                @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                      onsubmit="return confirm('Benutzer {{ $user->name }} wirklich löschen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-danger btn-sm">
                                        Löschen
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
