<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initialscale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>Vorlage</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<x-app-layout>
    <div class="heads">
<h1>Buchungen</h1>
<h2>Übersicht der Buchungen</h2>
weiß noch nicht was hiermit passiert, vieleicht redundant
</div>
<table>
<thead>
<tr>
<th>Buchungsnummer</th> {{-- kann später weg --}}
<th>Produktion</th>
<th>Material</th>
<th>Buchungsbeginn</th>
<th>Buchungsende</th>


</tr>
</thead>
<tbody>
@foreach ( $bookings as $booking )
<tr>

<td>{{$booking->id}}</td> {{-- kann später weg --}}
<td>Platzhalter</td>
<td>{{-- {{$booking->production->bezeichnung}} --}} Platzhalter</td>
<td>{{$booking->booking_start}}</td>   
<td>{{$booking->booking_end}}</td>

</tr>
@endforeach

</tbody>
</table>
</x-app-layout>
</body>
</html>