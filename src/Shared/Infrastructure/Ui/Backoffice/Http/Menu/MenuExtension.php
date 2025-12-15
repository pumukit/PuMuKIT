<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ui\Backoffice\Http\Menu;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class MenuExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private MenuBuilder $menuBuilder
    ) {}

    public function getGlobals(): array
    {
        return [
            'app_menu' => $this->menuBuilder->buildMenu(),
        ];
    }
}
