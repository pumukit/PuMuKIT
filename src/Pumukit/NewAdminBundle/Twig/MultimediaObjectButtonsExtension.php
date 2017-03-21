<?php

namespace Pumukit\NewAdminBundle\Twig;

use Pumukit\NewAdminBundle\Menu\ItemsList;

class MultimediaObjectButtonsExtension extends \Twig_Extension
{
    private $mmobjListButtons;
    private $mmobjMenu;

    public function __construct(ItemsList $mmobjListButtons, ItemsList $mmobjMenu)
    {
        $this->mmobjListButtons = $mmobjListButtons;
        $this->mmobjMenu = $mmobjMenu;
    }

    /**
     * Get name.
     */
    public function getName()
    {
        return 'pumukitadmin_button_extension';
    }

    /**
     * Get functions.
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('get_extra_buttons', array($this, 'getMmobjExtraButtons')),
            new \Twig_SimpleFunction('get_extra_menu_items', array($this, 'getMmobjExtraMenuItems')),
        );
    }

    public function getMmobjExtraButtons()
    {
        return $this->mmobjListButtons->items();
    }

    public function getMmobjExtraMenuItems()
    {
        return $this->mmobjMenu->items();
    }
}
