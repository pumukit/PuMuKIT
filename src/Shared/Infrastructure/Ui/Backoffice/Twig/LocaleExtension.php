<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ui\Backoffice\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LocaleExtension extends AbstractExtension
{
    public function __construct() {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('locale_text', $this->localeText(...)),
        ];
    }

    public function localeText(string $locale): string
    {
        return match ($locale) {
            'en' => 'Ingles',
            'es' => 'Español',
            'ca' => 'Catalan',
            'eu' => 'Euskera',
            'fr' => 'Frances',
            'gl' => 'Gallego',
            'val' => 'Valenciano',
            default => 'Ingles'
        };
    }
}
