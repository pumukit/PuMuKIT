<?php

namespace Pumukit\OpencastBundle\Services;

use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;

class OpencastService
{
    private $sbsConfiguration;
    private $sbsProfileName = null;
    private $generateSbs = false;
    private $useFlavour = false;
    private $sbsFlavour = null;
    private $urlPathMapping;
    private $jobService;
    private $profileService;
    private $multimediaObjectService;
    private $defaultVars;

    public function __construct(JobService $jobService, ProfileService $profileService, MultimediaObjectService $multimediaObjectService, array $sbsConfiguration = array(), array $urlMapping = array(), array $defaultVars = array())
    {
        $this->jobService = $jobService;
        $this->profileService = $profileService;
        $this->multimediaObjectService = $multimediaObjectService;
        $this->sbsConfiguration = $sbsConfiguration;
        $this->urlPathMapping = $urlMapping;
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
                $this->sbsProfileName = $this->sbsConfiguration['profile'];
            }
            if (isset($this->sbsConfiguration['use_flavour'])) {
                $this->useFlavour = $this->sbsConfiguration['use_flavour'];
            }
            if (isset($this->sbsConfiguration['flavour'])) {
                $this->sbsFlavour = $this->sbsConfiguration['flavour'];
            }
        }
    }

    /**
     * Gen SBS according to configuration in parameters
     *
     * @param MultimediaObject $multimediaObject
     * @param array            $opencastUrls
     * @return boolean
     */
    public function genAutoSbs(MultimediaObject $multimediaObject, $opencastUrls = array())
    {
        if (!$this->generateSbs) {
            return false;
        }

        if ($this->useFlavour) {
            $flavourTrack = null;
            foreach ($multimediaObject->getTracksWithTag($this->sbsFlavour) as $track) {
                if (!$track->isOnlyAudio()) {
                    $flavourTrack = $track;
                    break;
                }
            }

            if ($flavourTrack) {
                return $this->useTrackAsSbs($multimediaObject, $flavourTrack);
            }
        }

        return $this->generateSbsTrack($multimediaObject, $opencastUrls);
    }

    /**
     * Get path
     *
     * @param string $url
     * @return string
     */
    public function getPath($url)
    {
        foreach ($this->urlPathMapping as $m) {
            $path = str_replace($m['url'], $m['path'], $url);
            if (realpath($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Generate SBS Track
     *
     * @param MultimediaObject $multimediaObject
     * @param array            $opencastUrls
     * @rettun boolean
     */
    public function generateSbsTrack(MultimediaObject $multimediaObject, $opencastUrls = array())
    {
        if (!$this->generateSbs) {
            return false;
        }

        if (!$this->sbsProfileName) {
            return false;
        }

        $tracks = $multimediaObject->getTracks();
        if (!$tracks) {
            return false;
        }

        $track = $tracks[0];
        $path = $this->getPath($track->getUrl());

        $language = $multimediaObject->getProperty('opencastlanguage')?strtolower($multimediaObject->getProperty('opencastlanguage')):'en';

        $vars = $this->defaultVars;
        if ($opencastUrls) {
            $vars += array('ocurls' => $opencastUrls);
        }

        return $this->jobService->addJob($path, $this->sbsProfileName, 2, $multimediaObject, $language, array(), $vars);
    }

    private function useTrackAsSbs(MultimediaObject $multimediaObject, Track $track)
    {
        if (!$this->sbsProfileName) {
            return false;
        }

        $sbsProfile = $this->profileService->getProfile($this->sbsProfileName);

        $track->addTag('profile:'.$this->sbsProfileName);

        $tags = array('master', 'display');
        foreach ($tags as $tag) {
            if ($sbsProfile[$tag] && !$track->containsTag($tag)) {
                $track->addTag($tag);
            }
        }

        foreach (array_filter(preg_split('/[,\s]+/', $sbsProfile['tags'])) as $tag) {
            $track->addTag(trim($tag));
        }

        $multimediaObject = $this->multimediaObjectService->updateMultimediaObject($multimediaObject);

        return true;
    }
}
