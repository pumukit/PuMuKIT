<?php

namespace App\Series\Application\ViewSeries;

final class ViewSeriesResponse
{
    public function __construct(
        private $series,
        private iterable $multimediaObjects
    ) {}

    public function series() { return $this->series; }
    public function multimediaObjects(): iterable { return $this->multimediaObjects; }
}
