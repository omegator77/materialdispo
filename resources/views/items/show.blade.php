@extends('layouts.app')

@section('content')
    <h1>Item: {{ $item->bezeichnung }}</h1>
    <p>Beschreibung: {{ $item->description }}</p>

    <h2>Verknüpfte Produktionen</h2>
    <ul>
        @foreach ($item->productions as $production)
            <li>{{ $production->bezeichnung }} ({{ $production->booking_start }} - {{ $production->booking_end }})</li>
        @endforeach
    </ul>
@endsection
