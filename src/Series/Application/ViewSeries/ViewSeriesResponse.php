<?php

namespace App\Series\Application\ViewSeries;

use Pumukit\SchemaBundle\Document\Series;

final class ViewSeriesResponse
{
    public function __construct(
        private Series $series,
        private array $multimediaObjects,
        private string $tab
    ) {}

    public function series(): Series
    {
        return $this->series;
    }

    public function multimediaObjects(): array
    {
        return array_map(function ($mmobj) {
            return [
                'id' => $mmobj->getId(),
                'rank' => $mmobj->getRank(),
                'title' => $mmobj->getTitle(),
                'status' => $mmobj->getStringStatus($mmobj->getStatus()),
                'duration' => $mmobj->getDurationString(),
                'recordDate' => $mmobj->getRecordDate(),
                'publicDate' => $mmobj->getPublicDate(),
                'hide' => $mmobj->isHidden(),
                'actions' => [],
            ];
        }, $this->multimediaObjects);
    }

    public function tab(): string
    {
        return $this->tab;
    }
}
