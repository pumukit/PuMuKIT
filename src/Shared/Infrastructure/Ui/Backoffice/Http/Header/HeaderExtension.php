<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ui\Backoffice\Http\Header;

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
