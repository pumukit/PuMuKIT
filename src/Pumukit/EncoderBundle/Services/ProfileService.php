<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;

class ProfileService
{
    private $dm;
    private $repo;
    private $profiles;

    const STREAMSERVER_STORE = 'store';
    const STREAMSERVER_DOWNLOAD = 'download';
    const STREAMSERVER_WMV = 'wmv';
    const STREAMSERVER_FMS = 'fms';
    const STREAMSERVER_RED5 = 'red5';

    /**
     * Constructor
     */
    public function __construct(array $profiles, DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitEncoderBundle:Job');
        $this->profiles = $profiles;
    }

    /**
     * Get available profiles
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * Get master profiles
     *
     * @param boolean $master
     * @return array $profiles only master if true, only not master if false
     */
    public function getMasterProfiles($master)
    {
        return array_filter($this->profiles, function ($profile) use ($master) {
              return $profile['master'] === $master;
          });
    }

    /**
     * Get given profile
     * @param string the profile name (case sensitive)
     */
    public function getProfile($profile)
    {
        if (isset($this->profiles[$profile])){
            return $this->profiles[$profile];
        }

      return null;      
    }
}