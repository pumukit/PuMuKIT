<?php

namespace Pumukit\NewAdminBundle\Menu;

interface ItemInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * Get the uri for a menu item
     *
     * @return string
     */
    public function getUri();

    /**
     * @return string
     */
    public function getAccessRole();
}
