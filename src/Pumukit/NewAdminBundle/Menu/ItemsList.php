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

    public function add(ItemInterface $item): void
    {
        $this->items[] = $item;
    }

    public function items(): array
    {
        return $this->items;
    }
}
