<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\Common\Collections\Collection;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class CaptionService
{
    public static $mimeTypeCaptions = ['vtt', 'srt', 'dfxp'];

    /**
     * Get VTT captions.
     *
     * @return Collection
     */
    public function getCaptions(MultimediaObject $multimediaObject)
    {
        $mimeTypeCaptions = self::$mimeTypeCaptions;

        return $multimediaObject->getMaterials()->filter(function (Material $material) use ($mimeTypeCaptions) {
            return in_array($material->getMimeType(), $mimeTypeCaptions);
        });
    }

    public function hasCaptions(MultimediaObject $multimediaObject): bool
    {
        if (0 !== count($this->getCaptions($multimediaObject))) {
            return true;
        }

        return false;
    }
}
