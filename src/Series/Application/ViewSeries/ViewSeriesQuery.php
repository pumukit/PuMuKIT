<?php

namespace App\Series\Application\ViewSeries;

final class ViewSeriesQuery
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}
