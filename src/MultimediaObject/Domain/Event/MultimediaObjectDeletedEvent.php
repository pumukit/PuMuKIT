<?php

namespace App\MultimediaObject\Domain\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Contracts\EventDispatcher\Event;

final class MultimediaObjectDeletedEvent extends Event
{
    public const NAME = 'multimedia.deleted';

    public function __construct(
        private MultimediaObject $multimediaObject
    ) {}

    public function getMultimediaObject(): MultimediaObject
    {
        return $this->multimediaObject;
    }
}
