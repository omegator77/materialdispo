<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; margin: 0; padding: 0; background-color: #f9fafb;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px;">
        <h2 style="color: #111827;">Dry-Hire-Erinnerung: {{ $reminderLabel }}</h2>

        <p>
            <strong>Produktion:</strong> {{ $production->bezeichnung }}
        </p>

        <p>
            <strong>Zeitraum:</strong>
            {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
            – {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}
        </p>

        @if($dryHire->customer_email)
        <p><strong>Kunde:</strong> {{ $dryHire->customer_email }}</p>
        @endif

        <p>
            <strong>{{ $reminderType === 'start' ? 'Lieferung' : 'Rückgabe' }}:</strong>
            @if($reminderType === 'start')
                {{ \App\Models\DryHire::DELIVERY_TYPES[$dryHire->delivery_type] ?? 'noch nicht festgelegt' }}
            @else
                {{ \App\Models\DryHire::RETURN_TYPES[$dryHire->return_type] ?? 'noch nicht festgelegt' }}
            @endif
        </p>

        <p style="margin-top: 24px;">
            Bitte die {{ $reminderType === 'start' ? 'Übergabe des Materials an den Kunden' : 'Rücknahme des Materials vom Kunden' }}
            rechtzeitig organisieren.
        </p>

        <p style="color: #6b7280; font-size: 12px; margin-top: 32px;">
            {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
