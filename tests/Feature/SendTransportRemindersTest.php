<?php

use App\Mail\TransportReminderMail;
use App\Models\Item;
use App\Models\MailingList;
use App\Models\Mieter;
use App\Models\Mietvorgang;
use App\Models\MietvorgangReminderLog;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Vermietvorgang;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

function reminderMietvorgang(string $start, string $end, array $recipients = ['crew@example.com']): Mietvorgang
{
    $list = MailingList::create(['name' => 'Crew', 'is_default' => true]);
    foreach ($recipients as $email) {
        $list->recipients()->create(['email' => $email]);
    }

    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);
    $item = Item::create(['units_id' => Unit::create(['bezeichnung' => 'K'])->id, 'bezeichnung' => 'Kamera']);
    $mv = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => $start, 'rent_end' => $end]);
    $mv->items()->attach($item->id);

    return $mv;
}

beforeEach(function () {
    Carbon::setTestNow('2026-08-10 08:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('fälliger Mietbeginn sendet genau eine Mail pro Empfänger und protokolliert', function () {
    Mail::fake();
    $mv = reminderMietvorgang('2026-08-12', '2026-08-20', ['a@example.com', 'b@example.com']);

    $this->artisan('reminders:send-transport')->assertSuccessful();

    Mail::assertSent(TransportReminderMail::class, 2);
    expect(MietvorgangReminderLog::where('mietvorgang_id', $mv->id)->pluck('reminder_type')->all())->toBe(['start']);
});

test('ein zweiter Lauf sendet nichts erneut', function () {
    Mail::fake();
    reminderMietvorgang('2026-08-12', '2026-08-20');

    $this->artisan('reminders:send-transport');
    $this->artisan('reminders:send-transport');

    Mail::assertSent(TransportReminderMail::class, 1);
});

test('noch nicht fällige Erinnerungen werden nicht gesendet', function () {
    Mail::fake();
    reminderMietvorgang('2026-09-01', '2026-09-10');

    $this->artisan('reminders:send-transport')->assertSuccessful();

    Mail::assertNothingSent();
});

test('für bereits vergangene Termine werden keine Erinnerungen nachgeschickt', function () {
    Mail::fake();
    $mv = reminderMietvorgang('2026-07-01', '2026-07-05');

    $this->artisan('reminders:send-transport')->assertSuccessful();

    Mail::assertNothingSent();
    expect(MietvorgangReminderLog::where('mietvorgang_id', $mv->id)->count())->toBe(0);
});

test('Mietende wird erinnert, auch wenn der Beginn schon vorbei ist', function () {
    Mail::fake();
    $mv = reminderMietvorgang('2026-08-01', '2026-08-12');

    $this->artisan('reminders:send-transport')->assertSuccessful();

    expect(MietvorgangReminderLog::where('mietvorgang_id', $mv->id)->pluck('reminder_type')->all())->toBe(['end']);
});

test('Vermietvorgang wird ebenfalls erinnert', function () {
    Mail::fake();
    $list = MailingList::create(['name' => 'Crew', 'is_default' => true]);
    $list->recipients()->create(['email' => 'crew@example.com']);
    $item = Item::create(['units_id' => Unit::create(['bezeichnung' => 'K'])->id, 'bezeichnung' => 'Kamera']);
    $vv = Vermietvorgang::create(['mieter_id' => Mieter::create(['bezeichnung' => 'Kunde'])->id, 'rent_start' => '2026-08-12', 'rent_end' => '2026-08-20']);
    $vv->items()->attach($item->id);

    $this->artisan('reminders:send-transport')->assertSuccessful();

    Mail::assertSent(\App\Mail\VerleihReminderMail::class, 1);
});

test('der Erinnerungseintrag existiert schon, während die Mail versendet wird', function () {
    $mv = reminderMietvorgang('2026-08-12', '2026-08-20');
    $existierte = null;

    $pending = Mockery::mock();
    $pending->shouldReceive('send')->andReturnUsing(function () use ($mv, &$existierte) {
        $existierte = MietvorgangReminderLog::where('mietvorgang_id', $mv->id)->where('reminder_type', 'start')->exists();
    });
    Mail::shouldReceive('to')->andReturn($pending);

    $this->artisan('reminders:send-transport')->assertSuccessful();

    expect($existierte)->toBeTrue();
});

test('scheitern alle Mails, endet der Command mit Fehler und der Termin wird erneut versucht', function () {
    $mv = reminderMietvorgang('2026-08-12', '2026-08-20');

    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));
    $this->artisan('reminders:send-transport')->assertFailed();
    expect(MietvorgangReminderLog::where('mietvorgang_id', $mv->id)->count())->toBe(0);

    Mockery::close();
    Mail::swap(new Illuminate\Mail\MailManager(app()));
    Mail::fake();

    $this->artisan('reminders:send-transport')->assertSuccessful();
    Mail::assertSent(TransportReminderMail::class, 1);
    expect(MietvorgangReminderLog::where('mietvorgang_id', $mv->id)->count())->toBe(1);
});

test('der Scheduler führt die Erinnerungen höchstens stündlich aus und enthält keinen inspire-Eintrag', function () {
    $events = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events());

    $reminder = $events->first(fn ($e) => str_contains($e->command, 'reminders:send-transport'));

    expect($reminder)->not->toBeNull()
        ->and($reminder->expression)->not->toBe('* * * * *')
        ->and($events->contains(fn ($e) => str_contains($e->command, 'inspire')))->toBeFalse();
});
