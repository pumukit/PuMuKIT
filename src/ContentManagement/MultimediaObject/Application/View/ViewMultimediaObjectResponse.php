<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\View;

use Pumukit\SchemaBundle\Document\MultimediaObject;

final class ViewMultimediaObjectResponse
{
    public function __construct(
        public MultimediaObject $multimediaObject,
        public string $tab
    ) {}
}
