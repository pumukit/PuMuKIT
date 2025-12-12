<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Translation;

use App\Shared\Domain\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface as SymfonyTranslatorInterface;

final class SymfonyTranslator implements TranslatorInterface
{
    public function __construct(
        private readonly SymfonyTranslatorInterface $symfonyTranslator
    ) {}

    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->symfonyTranslator->trans($id, $parameters, $domain, $locale);
    }

    public function getLocale(): string
    {
        return $this->symfonyTranslator->getLocale();
    }
}
