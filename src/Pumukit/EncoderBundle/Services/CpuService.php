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

    // TODO - Move CPUs to configuration files
    private $cpus = array(
                       'CPU_LOCAL' => array(
                                            'id' => 1,
                                            'host' => '127.0.0.1',
                                            'max' => 1,
                                            'number' => 1,
                                            'type' => self::TYPE_LINUX,
                                            'user' => 'transco1',
                                            'password' => 'PUMUKIT',
                                            'description' => 'Pumukit transcoder'
                                            ),
                       'CPU_REMOTE' => array(
                                            'id' => 2,
                                            'host' => '192.168.5.123',
                                            'max' => 2,
                                            'number' => 1,
                                            'type' => self::TYPE_LINUX,
                                            'user' => 'transco2',
                                            'password' => 'PUMUKIT',
                                            'description' => 'Pumukit transcoder'
                                            )
                       );
    
    /**
     * Constructor
     */
    public function __construct(DocumentManager $documentManager)
    {
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