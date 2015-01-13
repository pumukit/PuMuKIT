<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;

class CpuService
{
    private $dm;
    private $repo;

    const TYPE_LINUX = 'linux';
    const TYPE_WINDOWS = 'windows';
    const TYPE_GSTREAMER = 'gstreamer';
    
    /**
     * Constructor
     */
    public function __construct(array $cpus, DocumentManager $documentManager)
    {
        $this->cpus = $cpus;
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitEncoderBundle:Job');
    }

    /**
     * Get available free cpus
     */
    public function getFreeCpu($type = null)
    {
        $executingJobs = $this->repo->findWithStatus(array(Job::STATUS_EXECUTING));

        foreach ($this->cpus as $cpu){
            $busy = 0;
            foreach ($executingJobs as $job){
                if ($cpu['id'] === $job->getCpuId()){
                    $busy++;
                }
            }
            if (($busy < $cpu['max']) && (($cpu['type'] == $type) || (null == $type))){
                return $cpu;                
            }
        }

        return null;
    }

    /**
     * Get Cpu by name
     */
    public function getCpuByName($name)
    {
        if (isset($this->cpus[strtoupper($name)])){
            return $this->cpus[strtoupper($name)];
        }

        return null;
    }

    /**
     * Get Cpus
     */
    public function getCpus()
    {
        return $this->cpus;
    }
}