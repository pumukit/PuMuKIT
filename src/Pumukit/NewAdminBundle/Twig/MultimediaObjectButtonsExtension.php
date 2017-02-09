<?php

namespace Pumukit\NewAdminBundle\Twig;

use Pumukit\NewAdminBundle\Menu\ItemsList;

class MultimediaObjectButtonsExtension extends \Twig_Extension
{
    public function __construct(ItemsList $mmobjListButtons)
    {
        $this->mmobjListButtons = $mmobjListButtons;
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
        );
    }

    public function getMmobjExtraButtons()
    {
        return $this->mmobjListButtons->items();
    }
}
