<?php

namespace Pumukit\OpencastBundle\Services;

use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\SchemaBundle\Document\MultimediaObject;


class OpencastService
{
    private $sbsProfile;
    private $jobService;
    private $urlPathMapping;
    private $defaultVars;

    public function __construct($sbsProfile, JobService $jobService, array $urlPathMapping = array(), array $defaultVars = array())
    {
        $this->sbsProfile = $sbsProfile;
        $this->jobService = $jobService;
        $this->urlPathMapping = $urlPathMapping;
        $this->defaultVars = $defaultVars;
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

        $vars = $this->defaultVars;
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
