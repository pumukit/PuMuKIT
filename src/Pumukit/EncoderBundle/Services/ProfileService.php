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
     * Get given profile
     */
    public function getProfile($profile)
    {
        if (isset($this->profiles[strtoupper($profile)])){
            return $this->profiles[strtoupper($profile)];
        }

      return null;      
    }
}