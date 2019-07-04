<?php

namespace Pumukit\NewAdminBundle\Twig;

use Pumukit\NewAdminBundle\Menu\ItemsList;

class MultimediaObjectButtonsExtension extends \Twig_Extension
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

    /**
     * Get functions.
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_extra_buttons', [$this, 'getMmobjExtraButtons']),
            new \Twig_SimpleFunction('get_extra_menu_items', [$this, 'getMmobjExtraMenuItems']),
            new \Twig_SimpleFunction('get_extra_series_menu_items', [$this, 'getSeriesExtraMenuItems']),
        ];
    }

    public function getMmobjExtraButtons()
    {
        return $this->mmobjListButtons->items();
    }

    public function getSeriesExtraMenuItems()
    {
        return $this->seriesMenu->items();
    }

    public function getMmobjExtraMenuItems()
    {
        return $this->mmobjMenu->items();
    }
}
