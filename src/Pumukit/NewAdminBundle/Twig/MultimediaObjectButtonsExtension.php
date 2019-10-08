<?php

namespace Pumukit\NewAdminBundle\Twig;

use Pumukit\NewAdminBundle\Menu\ItemsList;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MultimediaObjectButtonsExtension extends AbstractExtension
{
    private $mmobjListButtons;
    private $mmobjMenu;
    private $seriesMenu;

    public function __construct(ItemsList $mmobjListButtons, ItemsList $mmobjMenu, ItemsList $seriesMenu)
    {
        $this->mmobjListButtons = $mmobjListButtons;
        $this->mmobjMenu = $mmobjMenu;
        $this->seriesMenu = $seriesMenu;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_extra_buttons', [$this, 'getMmobjExtraButtons']),
            new TwigFunction('get_extra_menu_items', [$this, 'getMmobjExtraMenuItems']),
            new TwigFunction('get_extra_series_menu_items', [$this, 'getSeriesExtraMenuItems']),
        ];
    }

    public function getMmobjExtraButtons(): array
    {
        return $this->mmobjListButtons->items();
    }

    public function getSeriesExtraMenuItems(): array
    {
        return $this->seriesMenu->items();
    }

    public function getMmobjExtraMenuItems(): array
    {
        return $this->mmobjMenu->items();
    }
}
