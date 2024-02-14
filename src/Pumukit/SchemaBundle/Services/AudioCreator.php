<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\MediaCreator;
use Pumukit\SchemaBundle\Document\MultimediaObject;

final class AudioCreator extends MediaCreator
{
    public static function create(MultimediaObject $multimediaObject, Job $job)
    {
        // TODO: Implement create() method.
    }
}
