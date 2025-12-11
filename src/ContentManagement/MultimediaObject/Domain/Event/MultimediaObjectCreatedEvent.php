<?php

namespace App\ContentManagement\MultimediaObject\Domain\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Contracts\EventDispatcher\Event;

final class MultimediaObjectCreatedEvent extends Event
{
    public const NAME = 'multimedia.created';

    public function __construct(
        private MultimediaObject $multimediaObject
    ) {}

    public function getMultimediaObject(): MultimediaObject
    {
        return $this->multimediaObject;
    }
}
