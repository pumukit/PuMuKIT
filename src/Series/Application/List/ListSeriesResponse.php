<?php

namespace App\Series\Application\List;

final class ListSeriesResponse
{
    public array $series;
    public int $total;

    public function __construct(array $series, int $total)
    {
        $this->series = $series;
        $this->total = $total;
    }
}
