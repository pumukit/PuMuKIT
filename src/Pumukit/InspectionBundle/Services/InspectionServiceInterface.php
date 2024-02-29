<?php

declare(strict_types=1);

namespace Pumukit\InspectionBundle\Services;

use Pumukit\SchemaBundle\Document\ValueObject\Path;

interface InspectionServiceInterface
{
    public function getFileMetadata(?Path $path);
}
