<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    /**
     * Zusätzlich zum Limit je E-Mail+IP (5 Versuche/Minute) gilt ein Limit je
     * E-Mail (20 Versuche/15 Minuten), damit wechselnde Absender-IPs das
     * Brute-Force-Limit nicht aushebeln.
     */
    private const EMAIL_MAX_ATTEMPTS = 20;

    private const EMAIL_DECAY_SECONDS = 900;

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->emailThrottleKey(), self::EMAIL_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->emailThrottleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $perIp = RateLimiter::tooManyAttempts($this->throttleKey(), 5);
        $perEmail = RateLimiter::tooManyAttempts($this->emailThrottleKey(), self::EMAIL_MAX_ATTEMPTS);

        if (! $perIp && ! $perEmail) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            $perIp ? RateLimiter::availableIn($this->throttleKey()) : 0,
            $perEmail ? RateLimiter::availableIn($this->emailThrottleKey()) : 0,
        );

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * Throttle-Key nur nach E-Mail (unabhängig von der Absender-IP).
     */
    public function emailThrottleKey(): string
    {
        return 'login-email|'.Str::transliterate(Str::lower($this->string('email')));
    }
}
