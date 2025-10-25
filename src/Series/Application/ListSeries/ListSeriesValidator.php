<?php

namespace App\Series\Application\ListSeries;

class ListSeriesValidator
{
    public static function validate(ListSeriesRequest $request): void
    {
        if ($request->page !== null && $request->page < 1) {
            throw new \InvalidArgumentException('El número de página debe ser mayor que 0.');
        }

        if ($request->limit !== null && $request->limit < 1) {
            throw new \InvalidArgumentException('El límite debe ser mayor que 0.');
        }
    }
}
