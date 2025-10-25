<?php

namespace App\Series\Application\ListSeries;

class ListSeriesRequest
{
    public ?int $page;
    public ?int $limit;
    public array $filters;

    public function __construct(?int $page = 1, ?int $limit = 20, array $filters = [])
    {
        $this->page = $page;
        $this->limit = $limit;
        $this->filters = $filters;
    }
}
