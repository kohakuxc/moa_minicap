<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.turnstile.enabled')
            && filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret_key'));
    }

    public function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        try {
            $response = Http::timeout(8)
                ->asForm()
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array_filter([
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]));

            return $response->successful() && (bool) $response->json('success');
        } catch (\Throwable $e) {
            Log::warning('Cloudflare Turnstile verification request failed.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
