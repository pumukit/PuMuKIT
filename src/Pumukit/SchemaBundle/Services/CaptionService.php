<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class CaptionService
{
    public static $mimeTypeCaptions = ['vtt', 'srt', 'dfxp'];

    /**
     * Get VTT captions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCaptions(MultimediaObject $multimediaObject)
    {
        $mimeTypeCaptions = self::$mimeTypeCaptions;

        return $multimediaObject->getMaterials()->filter(function (Material $material) use ($mimeTypeCaptions) {
            return in_array($material->getMimeType(), $mimeTypeCaptions);
        });
    }

    /**
     *
     * @return bool
     */
    public function hasCaptions(MultimediaObject $multimediaObject)
    {
        if(0 !== count($this->getCaptions($multimediaObject))) {
            return true;
        }

        return false;
    }
}
