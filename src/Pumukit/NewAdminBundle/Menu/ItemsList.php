<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Menu;

class ItemsList
{
    private $items;

    public function __construct()
    {
        $this->items = [];
    }

    public function add(ItemInterface $item): void
    {
        $this->items[] = $item;
    }

    public function items(?string $serviceTag = null): array
    {
        if ($serviceTag) {
            return array_filter($this->items, static function (ItemInterface $item) use ($serviceTag) {
                return $item->getServiceTag() === $serviceTag;
            });
        }

        return $this->items;
    }
}
