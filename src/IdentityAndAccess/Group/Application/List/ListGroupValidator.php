<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\List;

final class ListGroupValidator
{
    public static function validate(ListGroupRequest $request): void
    {
        if ($request->page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0');
        }

        if ($request->limit < 1) {
            throw new \InvalidArgumentException('Limit must be greater than 0');
        }

        $validOrders = ['asc', 'desc'];
        if (!in_array(strtolower($request->order), $validOrders, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid order: %s. Valid orders are: %s', $request->order, implode(', ', $validOrders))
            );
        }
    }
}
