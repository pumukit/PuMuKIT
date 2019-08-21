<?php

namespace Pumukit\NewAdminBundle\Menu;

class ItemsList
{
    /**
     * @var array
     */
    private $items;

    public function __construct()
    {
        $this->items = [];
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
