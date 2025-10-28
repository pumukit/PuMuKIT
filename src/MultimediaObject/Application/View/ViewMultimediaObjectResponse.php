<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\View;

use Pumukit\SchemaBundle\Document\MultimediaObject;

final class ViewMultimediaObjectResponse
{
    public function __construct(
        public readonly MultimediaObject $multimediaObject,
        public readonly string $tab
    ) {}
}
