<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Domain\Event;

use App\Shared\Domain\DomainEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;

final readonly class MultimediaObjectDeletedEvent extends DomainEvent
{
    public function __construct(
        public MultimediaObject $multimediaObject
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'multimedia.deleted';
    }
}
