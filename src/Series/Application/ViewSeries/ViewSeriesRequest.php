<?php

namespace App\Series\Application\ViewSeries;

final class ViewSeriesRequest
{
    public string $id;
    public ?int $page;
    public ?int $limit;
    public array $filters;
    public string $sort;
    public string $order;

    public function __construct(string $id, ?int $page = 1, ?int $limit = 20, array $filters = [], string $sort = 'rank', string $order = 'asc')
    {
        $this->id = $id;
        $this->page = $page;
        $this->limit = $limit;
        $this->filters = $filters;
        $this->sort = $sort;
        $this->order = $order;
    }
}
