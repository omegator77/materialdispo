<div class="overflow-x-auto  w-4/5 mx-auto mt-4 bg bg-white border-gray-400 border rounded-md shadow-md overflow-hidden">
    <table class="border-collapse border border-slate-400 w-full h-full bg-white">
        <thead class="text-left bg-orange-400">
            <tr>
                <th class="text left pl-4 ">Bezeichnung</th>
                <th class="text left pl-4">Beschreibung</th>
        </thead>
        <tbody>
            @foreach ( $units as $unit )
            <tr class="even:bg-orange-200">
                <td class="text left pl-4 hover:font-bold">
                    <a href="{{ route('units.show', $unit->id) }}">
                        {{ $unit->bezeichnung }}
                    </a>
                </td>
                <td class="text left pl-4">{{$unit->description}}</td>

            </tr>
            @endforeach
        </tbody>
    </table>
</div>