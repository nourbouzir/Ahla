<?php

namespace App\Contracts\Fakes;

use App\Contracts\Translation\Translator;

class NullTranslator implements Translator
{
    /** @param array<string, string> $translations source-language|target-language|text => translated text */
    public function __construct(private array $translations = []) {}

    public function setTranslations(array $translations): void
    {
        $this->translations = $translations;
    }

    public function translate(string $text, string $from, string $to): string
    {
        return $this->translations["{$from}|{$to}|{$text}"] ?? $text;
    }
}
