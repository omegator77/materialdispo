<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Benutzerverwaltung</h2>
            <a href="{{ route('users.create') }}"
               class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                Neuer Benutzer
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl w-11/12 mx-auto mt-6">

        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-300 text-green-800 rounded px-4 py-3">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-300 text-red-800 rounded px-4 py-3">{{ session('error') }}</div>
        @endif

        <div class="bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="text-left px-4 py-3">Name</th>
                        <th class="text-left px-4 py-3">E-Mail</th>
                        <th class="text-left px-4 py-3">Rolle</th>
                        <th class="text-right px-4 py-3">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr class="border-b hover:bg-gray-50 {{ $user->id === auth()->id() ? 'bg-blue-50' : '' }}">
                        <td class="px-4 py-3 font-medium">
                            {{ $user->name }}
                            @if($user->id === auth()->id())
                                <span class="text-xs text-blue-500 ml-1">(du)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
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
                                   class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-1 px-3 rounded text-xs">
                                    Bearbeiten
                                </a>
                                @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                      onsubmit="return confirm('Benutzer {{ $user->name }} wirklich löschen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-3 rounded text-xs">
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
