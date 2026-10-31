<?php

namespace App\Modules\Messaging\Services;

use App\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Translator adapter for a LibreTranslate-compatible HTTP service (ADR 0006).
 * Sends only {text, source, target}. Any problem (no URL configured, timeout,
 * error status, empty answer) throws, so callers fall back to the original.
 */
class LibreTranslateTranslator implements Translator
{
    private const SUPPORTED = ['en', 'ar', 'fr'];

    public function translate(string $text, string $from, string $to): string
    {
        if (! in_array($from, self::SUPPORTED, true) || ! in_array($to, self::SUPPORTED, true)) {
            throw new RuntimeException("Unsupported translation pair {$from}->{$to}.");
        }

        $url = rtrim((string) config('ahla.translation.url'), '/');

        if ($url === '') {
            throw new RuntimeException('No translation service is configured.');
        }

        $response = Http::timeout((int) config('ahla.translation.timeout'))
            ->acceptJson()->asJson()
            ->post($url.'/translate', array_filter([
                'q' => $text,
                'source' => $from,
                'target' => $to,
                'format' => 'text',
                'api_key' => config('ahla.translation.api_key') ?: null,
            ]))
            ->throw();

        $translated = $response->json('translatedText');

        if (! is_string($translated) || $translated === '') {
            throw new RuntimeException('The translation service returned no text.');
        }

        return $translated;
    }
}
