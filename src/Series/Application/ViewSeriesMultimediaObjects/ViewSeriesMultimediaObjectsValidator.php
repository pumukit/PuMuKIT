<?php

namespace App\Series\Application\ViewSeriesMultimediaObjects;

final class ViewSeriesMultimediaObjectsValidator
{
    public static function validate(ViewSeriesMultimediaObjectsRequest $request): void
    {
        if (null !== $request->page && $request->page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0');
        }

        if (null !== $request->limit && $request->limit < 1) {
            throw new \InvalidArgumentException('Limit must be greater than 0');
        }
    }
}
