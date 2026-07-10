<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private const KEYS = [
        'slack_reminder_channel',
        'slack_production_channel',
        'reminder_days_before_start',
        'reminder_days_before_end',
    ];

    private const BOOLEAN_KEYS = [
        'slack_reminder_enabled',
        'slack_production_enabled',
    ];

    public function edit()
    {
        $settings = collect(self::KEYS)->mapWithKeys(fn (string $key) => [$key => Setting::get($key)]);

        foreach (self::BOOLEAN_KEYS as $key) {
            $settings[$key] = Setting::get($key, '1') === '1';
        }

        return view('settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'slack_reminder_channel' => ['nullable', 'string', 'max:255'],
            'slack_production_channel' => ['nullable', 'string', 'max:255'],
            'reminder_days_before_start' => ['nullable', 'integer', 'min:0', 'max:60'],
            'reminder_days_before_end' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);

        foreach (self::KEYS as $key) {
            Setting::set($key, isset($validated[$key]) ? (string) $validated[$key] : null);
        }

        foreach (self::BOOLEAN_KEYS as $key) {
            Setting::set($key, $request->boolean($key) ? '1' : '0');
        }

        return redirect()->route('settings.edit')->with('success', 'Einstellungen gespeichert.');
    }
}
