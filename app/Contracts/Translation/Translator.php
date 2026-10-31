<?php

namespace App\Contracts\Translation;

interface Translator
{
    public function translate(string $text, string $from, string $to): string;
}
