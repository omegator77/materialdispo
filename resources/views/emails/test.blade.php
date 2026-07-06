<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; margin: 0; padding: 0; background-color: #f9fafb;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px;">
        <h2 style="color: #111827;">Test-E-Mail</h2>

        <p>
            Das ist eine Test-E-Mail von {{ config('app.name') }}, ausgelöst von
            <strong>{{ $sender->name }}</strong> ({{ $sender->email }}) am {{ $sentAt->format('d.m.Y H:i') }} Uhr.
        </p>

        <p>
            Wenn du das hier liest, funktioniert der Mailversand über die aktuell konfigurierten SMTP-Zugangsdaten korrekt.
        </p>

        <p style="color: #6b7280; font-size: 12px; margin-top: 32px;">
            {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
