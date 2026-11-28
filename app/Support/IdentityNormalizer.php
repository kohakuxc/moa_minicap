<?php

namespace App\Support;

use Illuminate\Support\Str;

class IdentityNormalizer
{
    public static function normalizeName(?string $name): string
    {
        return Str::squish((string) $name);
    }

    public static function normalizeEmail(?string $email): string
    {
        return Str::lower(trim((string) $email));
    }

    /**
     * Canonicalize an email address to prevent +alias and Gmail dot bypasses.
     */
    public static function canonicalEmail(?string $email): string
    {
        $normalized = self::normalizeEmail($email);
        if (! str_contains($normalized, '@')) {
            return $normalized;
        }

        [$local, $domain] = explode('@', $normalized, 2);

        if ($domain === 'googlemail.com') {
            $domain = 'gmail.com';
        }

        if (str_contains($local, '+')) {
            $local = strstr($local, '+', true) ?: $local;
        }

        if ($domain === 'gmail.com') {
            $local = str_replace('.', '', $local);
        }

        return $local.'@'.$domain;
    }

    /**
     * Normalize phone numbers into a consistent canonical / E.164 representation
     * so formatting variations (spaces, dashes, 09 vs +639) collide properly.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return null;
        }

        $stripped = preg_replace('/[\s\-\(\)\.]+/', '', $raw) ?? '';
        if ($stripped === '') {
            return null;
        }

        if (str_starts_with($stripped, '00')) {
            $stripped = '+'.substr($stripped, 2);
        }

        $hasPlus = str_starts_with($stripped, '+');
        $digits = preg_replace('/\D+/', '', $stripped) ?? '';
        if ($digits === '') {
            return null;
        }

        // Convert common Philippines mobile formats to E.164 (+639XXXXXXXXX)
        if (! $hasPlus) {
            if (preg_match('/^09\d{9}$/', $digits) === 1) {
                return '+63'.substr($digits, 1);
            }
            if (preg_match('/^639\d{9}$/', $digits) === 1) {
                return '+'.$digits;
            }
            if (preg_match('/^9\d{9}$/', $digits) === 1) {
                return '+63'.$digits;
            }

            return $digits;
        }

        return '+'.$digits;
    }
}
