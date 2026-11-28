<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsService
{
    /**
     * Send a transactional SMS message using the configured driver (Brevo or log).
     */
    public function send(string $recipient, string $content): void
    {
        $driver = $this->resolveDriver();

        if ($driver === 'log') {
            Log::info('SMS OTP dispatched (log driver).', [
                'recipient' => $recipient,
                'content' => $content,
            ]);

            return;
        }

        if ($driver !== 'brevo') {
            throw new RuntimeException("Unsupported SMS driver [{$driver}].");
        }

        $apiKey = trim((string) config('services.brevo.key'));
        if ($apiKey === '') {
            throw new RuntimeException('Brevo API key is not configured for SMS.');
        }

        $sender = preg_replace('/[^A-Za-z0-9]/', '', (string) config('services.brevo.sms_sender', 'MOAClinic'));
        $sender = substr($sender !== '' ? $sender : 'MOAClinic', 0, 11);
        $endpoint = (string) config('services.brevo.sms_endpoint', 'https://api.brevo.com/v3/transactionalSMS/sms');

        $response = Http::timeout(10)
            ->acceptJson()
            ->withHeaders(['api-key' => $apiKey])
            ->post($endpoint, [
                'sender' => $sender,
                'recipient' => $recipient,
                'content' => $content,
                'type' => 'transactional',
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                sprintf('Brevo SMS API failed (%d): %s', $response->status(), $response->body())
            );
        }
    }

    public function sendOtp(string $recipient, string $otp, int $ttlMinutes = 10): void
    {
        $appName = config('app.name', 'MOA');
        $this->send(
            $recipient,
            "Your {$appName} verification code is {$otp}. It expires in {$ttlMinutes} minutes. Do not share this code."
        );
    }

    public function resolveDriver(): string
    {
        $driver = strtolower(trim((string) config('services.sms.driver', 'auto')));

        if ($driver === 'auto' || $driver === '') {
            return filled(config('services.brevo.key')) ? 'brevo' : 'log';
        }

        return $driver;
    }
}
