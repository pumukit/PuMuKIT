<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\Find;

use Pumukit\SchemaBundle\Document\MultimediaObject;

final class FindMultimediaObjectResponse
{
    public function __construct(
        public readonly MultimediaObject $multimediaObject
    ) {}
}
