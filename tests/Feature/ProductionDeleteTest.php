<?php

use App\Models\Production;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('deleting a production is recorded in the activity log', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = Production::create([
        'bezeichnung' => 'Test-Produktion',
        'booking_start' => '2026-10-01',
        'booking_end' => '2026-10-05',
    ]);

    $this->actingAs($admin)->delete(route('productions.destroy', $production->id))
        ->assertRedirect('/productions');

    expect(Production::find($production->id))->toBeNull();

    expect(
        DB::table('activity_log')
            ->where('log_name', 'production')
            ->where('event', 'deleted')
            ->where('subject_id', $production->id)
            ->exists()
    )->toBeTrue();
});
