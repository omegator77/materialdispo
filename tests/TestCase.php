<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Verhindert, dass Tests versehentlich echte HTTP-Requests auslösen
        // (z. B. reale Slack-Nachrichten über SlackVorgangSync) — jeder nicht
        // per Http::fake() abgedeckte Request lässt den Test fehlschlagen,
        // statt tatsächlich rauszugehen.
        Http::preventStrayRequests();
    }
}
