{{-- Einheitliche Anzeige der Flash-Meldungen (success / warning / error). --}}
@if(session('success'))
<div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
    {{ session('success') }}
</div>
@endif

@if(session('warning'))
<div class="bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded">
    {{ session('warning') }}
</div>
@endif

@if(session('error'))
<div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
    {{ session('error') }}
</div>
@endif
