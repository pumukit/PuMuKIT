<?php

namespace App\Series\Application\ViewSeries;

use Pumukit\SchemaBundle\Document\Series;

final class ViewSeriesResponse
{
    public function __construct(
        private Series $series,
        private iterable $multimediaObjects,
        private string $tab
    ) {}

    public function series(): Series
    {
        return $this->series;
    }

    public function multimediaObjects(): iterable
    {
        return $this->multimediaObjects;
    }

    public function tab(): string
    {
        return $this->tab;
    }
}
