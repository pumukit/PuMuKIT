<?php

namespace App\Series\Application\ViewSeriesEvents;

class ViewSeriesEventsValidator
{
    public static function validate(ViewSeriesEventsRequest $request): void
    {
        if (null !== $request->page && $request->page < 1) {
            throw new \InvalidArgumentException('El número de página debe ser mayor que 0.');
        }

        if (null !== $request->limit && $request->limit < 1) {
            throw new \InvalidArgumentException('El límite debe ser mayor que 0.');
        }
    }
}
