<?php

use App\Models\User;
use App\Services\SlackUserResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.slack.bot_token' => 'xoxb-test']);
    Cache::flush();
});

test('findet App-Benutzer per E-Mail und cached den Treffer', function () {
    $user = User::factory()->create(['email' => 'crew@example.com']);
    Http::fake(['slack.com/api/users.info*' => Http::response(['ok' => true, 'user' => ['profile' => ['email' => 'crew@example.com']]])]);

    expect(app(SlackUserResolver::class)->resolve('U1')?->id)->toBe($user->id);
    expect(app(SlackUserResolver::class)->resolve('U1')?->id)->toBe($user->id);

    Http::assertSentCount(1);
});

test('Fehler und Nichttreffer werden nicht gecacht', function () {
    $user = User::factory()->create(['email' => 'neu@example.com']);
    Http::fake(['slack.com/api/users.info*' => Http::sequence()
        ->push(['ok' => false, 'error' => 'user_not_found'])
        ->push(['ok' => true, 'user' => ['profile' => ['email' => 'neu@example.com']]])
        ->push(['ok' => true, 'user' => ['profile' => ['email' => 'niemand@example.com']]])
        ->push(['ok' => true, 'user' => ['profile' => ['email' => 'niemand@example.com']]])]);

    expect(app(SlackUserResolver::class)->resolve('U2'))->toBeNull();
    expect(app(SlackUserResolver::class)->resolve('U2')?->id)->toBe($user->id);
    expect(app(SlackUserResolver::class)->resolve('U2')?->id)->toBe($user->id);
    expect(app(SlackUserResolver::class)->resolve('U3'))->toBeNull();
    expect(app(SlackUserResolver::class)->resolve('U3'))->toBeNull();
    Http::assertSentCount(4);
});

test('ConnectionException ergibt null', function () {
    Http::fake(fn () => throw new ConnectionException('down'));

    expect(app(SlackUserResolver::class)->resolve('U4'))->toBeNull();
});

test('fehlende ID oder fehlender Token ergeben null ohne Aufruf', function () {
    Http::fake();

    expect(app(SlackUserResolver::class)->resolve(null))->toBeNull();
    config(['services.slack.bot_token' => null]);
    expect(app(SlackUserResolver::class)->resolve('U5'))->toBeNull();
    Http::assertNothingSent();
});

test('slackIdFor nutzt users.lookupByEmail', function () {
    $user = User::factory()->create(['email' => 'crew@example.com']);
    Http::fake(['slack.com/api/users.lookupByEmail*' => Http::sequence()
        ->push(['ok' => true, 'user' => ['id' => 'U77']])
        ->push(['ok' => false, 'error' => 'users_not_found'])
        ->pushFailedConnection()]);

    expect(app(SlackUserResolver::class)->slackIdFor($user))->toBe('U77');
    Http::assertSent(fn ($r) => str_contains($r->url(), 'users.lookupByEmail') && str_contains($r->url(), 'crew%40example.com'));

    expect(app(SlackUserResolver::class)->slackIdFor($user))->toBeNull();

    expect(app(SlackUserResolver::class)->slackIdFor($user))->toBeNull();
});
