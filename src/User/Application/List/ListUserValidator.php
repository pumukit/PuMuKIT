<?php

declare(strict_types=1);

namespace App\User\Application\List;

final class ListUserValidator
{
    public static function validate(ListUserRequest $request): void
    {
        if ($request->page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0');
        }

        if ($request->limit < 1) {
            throw new \InvalidArgumentException('Limit must be greater than 0');
        }

        $validSortFields = ['username', 'email', 'fullName', 'enabled', 'origin'];
        if (!in_array($request->sort, $validSortFields, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid sort field: %s. Valid fields are: %s', $request->sort, implode(', ', $validSortFields))
            );
        }

        $validOrders = ['asc', 'desc'];
        if (!in_array(strtolower($request->order), $validOrders, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid order: %s. Valid orders are: asc, desc', $request->order)
            );
        }
    }
}
