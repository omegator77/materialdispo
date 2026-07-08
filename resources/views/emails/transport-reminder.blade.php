<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; margin: 0; padding: 0; background-color: #f9fafb;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px;">
        <h2 style="color: #111827;">Transport-Erinnerung: {{ $reminderLabel }} — {{ $mietvorgang->bezeichnung ?? $supplier?->bezeichnung }}</h2>

        <p>
            <strong>Vermieter:</strong> {{ $supplier?->bezeichnung ?? 'Vermieter gelöscht' }}
            @if($supplier?->kontakt) ({{ $supplier->kontakt }}) @endif
            @if($supplier?->phone) — Tel: {{ $supplier->phone }} @endif
            @if($supplier?->email) — {{ $supplier->email }} @endif
        </p>

        <p>
            <strong>Mietzeitraum:</strong>
            {{ $mietvorgang->rent_start->format('d.m.Y') }} – {{ $mietvorgang->rent_end->format('d.m.Y') }}
        </p>

        <p>
            <strong>Transportart ({{ $reminderType === 'start' ? 'Hinweg' : 'Rückweg' }}):</strong>
            @if($reminderType === 'start')
                {{ $mietvorgang->transport_type_start ?: 'noch nicht festgelegt' }}
            @else
                {{ $mietvorgang->transport_type_end ?: 'noch nicht festgelegt' }}
            @endif
        </p>

        <h3 style="color: #111827; margin-top: 24px;">Geräte</h3>
        <ul>
            @foreach($items as $item)
            <li>{{ $item->bezeichnung }} @if($item->nummer)({{ $item->nummer }})@endif</li>
            @endforeach
        </ul>

        @if($productions->isNotEmpty())
        <h3 style="color: #111827; margin-top: 24px;">Benötigt für</h3>
        <ul>
            @foreach($productions as $production)
            <li>
                {{ $production->bezeichnung }}
                ({{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
                – {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }})
            </li>
            @endforeach
        </ul>
        @endif

        <p style="margin-top: 24px;">
            Bitte den Transport für
            {{ $reminderType === 'start' ? 'die Anlieferung/Abholung zu Mietbeginn' : 'die Rückgabe zu Mietende' }}
            rechtzeitig organisieren.
        </p>

        <p style="color: #6b7280; font-size: 12px; margin-top: 32px;">
            {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
