<?php

namespace App\Http\Controllers;

use App\Services\BestellungModals;
use App\Services\SlackUserResolver;
use App\Support\SlackSignature;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackCommandController extends Controller
{
    public function __construct(private SlackUserResolver $users) {}

    public function handle(Request $request)
    {
        SlackSignature::verify($request);

        $user = $this->users->resolve($request->input('user_id'));

        if (! $user || ! $user->isUser()) {
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => SlackInteractionController::DENIED_TEXT,
            ]);
        }

        try {
            $response = Http::withToken(config('services.slack.bot_token'))
                ->timeout(5)
                ->post('https://slack.com/api/views.open', [
                    'trigger_id' => $request->input('trigger_id'),
                    'view' => BestellungModals::neu(),
                ]);
            $ok = (bool) $response->json('ok');

            if (! $ok) {
                Log::warning('Slack views.open fehlgeschlagen: '.$response->json('error'));
            }
        } catch (ConnectionException $e) {
            Log::warning('Slack views.open nicht erreichbar: '.$e->getMessage());
            $ok = false;
        }

        if (! $ok) {
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => 'Formular konnte nicht geöffnet werden.',
            ]);
        }

        return response('', 200);
    }
}
