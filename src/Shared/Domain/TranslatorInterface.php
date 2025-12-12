<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface TranslatorInterface
{
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string;

    public function getLocale(): string;
}
