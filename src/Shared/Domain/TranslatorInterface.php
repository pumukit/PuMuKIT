<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Translation interface for framework-independent internationalization.
 *
 * This interface allows Domain and Application layers to remain independent
 * from Symfony's translation component, following the Dependency Inversion Principle.
 *
 * Implementation should be provided in the Infrastructure layer.
 */
interface TranslatorInterface
{
    /**
     * Translates the given message.
     *
     * @param string $id The message id (translation key)
     * @param array<string, mixed> $parameters An array of parameters for the message
     * @param string|null $domain The translation domain (e.g., 'series', 'user')
     * @param string|null $locale The locale (e.g., 'en', 'es', 'gl') or null to use the default
     *
     * @return string The translated string
     */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string;

    /**
     * Returns the current locale.
     *
     * @return string The current locale (e.g., 'en', 'es', 'gl')
     */
    public function getLocale(): string;
}

