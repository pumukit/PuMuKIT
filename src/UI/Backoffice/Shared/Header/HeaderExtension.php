<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Shared\Header;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class HeaderExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private HeaderBuilder $headerBuilder
    ) {}

    public function getGlobals(): array
    {
        return [
            'app_header_items' => $this->headerBuilder->buildHeader(),
        ];
    }
}
