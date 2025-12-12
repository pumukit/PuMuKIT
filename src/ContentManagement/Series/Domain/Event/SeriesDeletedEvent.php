<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Domain\Event;

use App\Shared\Domain\DomainEvent;
use Pumukit\SchemaBundle\Document\Series;

final readonly class SeriesDeletedEvent extends DomainEvent
{
    public function __construct(
        public Series $series
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'series.deleted';
    }
}
