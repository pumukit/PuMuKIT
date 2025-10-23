<?php

namespace App\Series\Application\ListSeries;

final class ListSeriesResponse
{
    public function __construct(private iterable $series) {}

    public function series(): iterable
    {
        return $this->series;
    }
}
