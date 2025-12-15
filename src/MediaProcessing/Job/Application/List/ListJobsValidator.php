<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Application\List;

final class ListJobsValidator
{
    public static function validate(ListJobsRequest $request): void
    {
        if ($request->page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0');
        }

        if ($request->limit < 1) {
            throw new \InvalidArgumentException('Limit must be greater than 0');
        }

        $validOrders = ['asc', 'desc'];
        if (!in_array($request->order, $validOrders, true)) {
            throw new \InvalidArgumentException('Order must be either "asc" or "desc"');
        }
    }
}
