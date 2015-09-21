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

        $freeCpus = array();
        foreach ($this->cpus as $name => $cpu){
            $busy = 0;
            foreach ($executingJobs as $job){
                if ($name === $job->getCpu()){
                    $busy++;
                }
            }
            if (($busy < $cpu['max']) && (($cpu['type'] == $type) || (null == $type))){
                $freeCpus[] = array(
                                    'name' => $name,
                                    'busy' => $busy,
                                    'max' => $cpu['max']
                                    );
            }
        }
        return $this->getOptimalCpu($freeCpus);
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

    private function getOptimalCpu($freeCpus=array())
    {
        $optimalCpu = null;
        foreach ($freeCpus as $cpu) {
            if (null == $optimalCpu) {
                $optimalCpu = $cpu;
            }
            if (($cpu['busy']/$cpu['max']) < ($optimalCpu['busy']/$optimalCpu['max'])) {
                $optimalCpu = $cpu['name'];
            } elseif (($cpu['busy'] === 0) && ($optimalCpu['busy'] === 0) && ($cpu['max'] > $optimalCpu['max'])) {
                $optimalCpu = $cpu['name'];
            }
        }
        if (isset($optimalCpu['name'])) {
            return $optimalCpu['name'];
        }
        return $optimalCpu;
    }
}