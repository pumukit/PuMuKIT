<?php

namespace App\Series\Application\ViewSeries;

final class ViewSeriesQuery
{
    public function __construct(private string $id, private string $tab = 'objects') {}

    public function id(): string
    {
        return $this->id;
    }

    public function tab(): string
    {
        return $this->tab;
    }
}
