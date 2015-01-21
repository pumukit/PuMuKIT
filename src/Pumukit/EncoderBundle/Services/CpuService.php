<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;

class CpuService
{
    private $dm;
    private $repo;
    private $cpus;

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

        foreach ($this->cpus as $name => $cpu){
            $busy = 0;
            foreach ($executingJobs as $job){
                if ($name === $job->getCpu()){
                    $busy++;
                }
            }
            if (($busy < $cpu['max']) && (($cpu['type'] == $type) || (null == $type))){
                return $name;
            }
        }

        return null;
    }

    /**
     * Get Cpu by name
     * @param string the cpu name (case sensitive)
     */
    public function getCpuByName($name)
    {
        if (isset($this->cpus[$name])){
            return $this->cpus[$name];
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

    /**
     * Is active
     *
     * Returns true if given cpu is active
     */
    public function isActive($cpu, $cmd = "")
    {
        // TODO
        return true;
    }
}