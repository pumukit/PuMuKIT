<?php

namespace Pumukit\NewAdminBundle\Menu;

class Chain
{
    private $items;

    public function __construct()
    {
        $this->items = array();
    }

    public function add(ItemInterface $item)
    {
        $this->items[] = $item;
    }

    public function items()
    {
        return $this->items;
    }
}
