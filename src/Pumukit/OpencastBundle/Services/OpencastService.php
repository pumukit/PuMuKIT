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


    public function genSbs(MultimediaObject $multimediaObject, $opencastUrls=array())
    {
        if (!$this->sbsProfile)
        return false;

        $tracks = $multimediaObject->getTracks();
        if (!$tracks)
            return false;

        $track = $tracks[0];
        $path = $this->getPath($track->getUrl());

        $language = $multimediaObject->getProperty('opencastlanguage')?strtolower($multimediaObject->getProperty('opencastlanguage')):'en';

        $vars = array();
        if ($opencastUrls) {
            $vars = array('ocurls' => $opencastUrls);
        }

        return $this->jobService->addJob($path, $this->sbsProfile, 2, $multimediaObject, $language, array(), $vars);
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
