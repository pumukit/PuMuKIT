<?php

namespace App\Series\Application\ListSeries;

class ListSeriesResponse
{
    public array $series;

    public function __construct(array $series)
    {
        $this->series = $series;
    }
}
