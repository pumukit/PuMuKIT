<?php

namespace Pumukit\SchemaBundle\Services;

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

        return $multimediaObject->getMaterials()->filter(function ($material) use ($mimeTypeCaptions) {
            return in_array($material->getMimeType(), $mimeTypeCaptions);
        });
    }
}
