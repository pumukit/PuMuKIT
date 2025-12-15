<?php

namespace App\ContentManagement\Series\Application\ViewSeriesMultimediaObjects;

final class ViewSeriesMultimediaObjectsValidator
{
    public static function validate(ViewSeriesMultimediaObjectsRequest $request): void
    {
        if (null !== $request->page && $request->page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0');
        }
        if (null === $request->limit) {
            return;
        }
        if ($request->limit >= 1) {
            return;
        }

        throw new \InvalidArgumentException('Limit must be greater than 0');
    }
}
