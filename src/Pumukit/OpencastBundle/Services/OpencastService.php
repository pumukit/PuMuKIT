<?php

namespace Pumukit\OpencastBundle\Services;

use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\SchemaBundle\Document\MultimediaObject;


class OpencastService
{
    private $sbsProfile;
    private $jobService;
    private $urlPathMapping;

    public function __construct($sbsProfile, JobService $jobService, array $urlPathMapping=array())
    {
        $this->sbsProfile = $sbsProfile;
        $this->jobService = $jobService;
        $this->urlPathMapping = $urlPathMapping;
    }


    public function genSbs(MultimediaObject $multimediaObject)
    {
        if (!$this->sbsProfile)
        return false;

        $tracks = $multimediaObject->getTracks();
        if (!$tracks)
        return false;

        $track = $tracks[0];
        $path = $this->getPath($track->getUrl());

        return $this->jobService->addUniqueJob($path, $this->sbsProfile, 0, $multimediaObject, "en");
    }


    public function getPath($url)
    {
        $path = $url;
        foreach($this->urlPathMapping as $m) {
            $path = str_replace($m["url"], $m["path"], $path);
        }
        return $path;
    }
}
