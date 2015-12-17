<?php

namespace Pumukit\OpencastBundle\Services;

use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\SchemaBundle\Document\MultimediaObject;


class OpencastService
{
    private $sbsConfiguration;
    private $sbsProfile = null;
    private $generateSbs = false;
    private $useFlavour = false;
    private $sbsFlavour = null;
    private $urlPathMapping;
    private $jobService;
    private $defaultVars;

    public function __construct($sbsConfiguration, JobService $jobService, array $defaultVars = array())
    {
        $this->sbsConfiguration = $sbsConfiguration;
        $this->jobService = $jobService;
        $this->defaultVars = $defaultVars;
        $this->initSbsConfiguration();
    }

    private function initSbsConfiguration()
    {
        if ($this->sbsConfiguration) {
            if (isset($this->sbsConfiguration['generate_sbs'])) {
                $this->generateSbs = $this->sbsConfiguration['generate_sbs'];
            }
            if (isset($this->sbsConfiguration['profile'])) {
                $this->sbsProfile = $this->sbsConfiguration['profile'];
            }
            if (isset($this->sbsConfiguration['use_flavour'])) {
                $this->useFlavour = $this->sbsConfiguration['use_flavour'];
            }
            if (isset($this->sbsConfiguration['flavour'])) {
                $this->sbsFlavour = $this->sbsConfiguration['flavour'];
            }
            if (isset($this->sbsConfiguration['url_mapping'])) {
                $this->urlPathMapping = $this->sbsConfiguration['url_mapping'];
            }
        }
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
            $vars += array('ocurls' => $opencastUrls);
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
