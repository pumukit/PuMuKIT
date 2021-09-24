<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Menu;

class BlocksList
{
    /**
     * @var array
     */
    private $items;

    public function __construct()
    {
        $this->items = [];
    }

    public function add(BlockInterface $item): void
    {
        $this->items[] = $item;
    }

    public function items(): array
    {
        return $this->items;
    }
}
