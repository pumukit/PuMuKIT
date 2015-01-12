<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;

class CpuService
{
    private $dm;
    private $repo;

    // TODO - Move CPUs to configuration files
    private $cpus = array(
                       'CPU_LOCAL' => array(
                                            'id' => 1,
                                            'ip' => '127.0.0.1',
                                            'max' => 1,
                                            'min' => 0,
                                            'number' => 0,
                                            'type' => 'linux',
                                            'user' => 'transCO1',
                                            'password' => 'PUMUKIT',
                                            'description' => 'Pumukit transcoder'
                                            ),
                       'CPU_REMOTE' => array(
                                            'id' => 2,
                                            'ip' => '192.168.5.123',
                                            'max' => 2,
                                            'min' => 0,
                                            'number' => 0,
                                            'type' => 'linux',
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
    public function getFreeCpu()
    {
        $freeCpus = array();

        $executingJobs = $this->repo->findWithStatus(array(Job::STATUS_EXECUTING));

        foreach ($this->cpus as $cpu){
            $busy = 0;
            foreach ($executingJobs as $job){
                if ($cpu['id'] === $job->getCpuId()){
                    $busy++;
                }
            }
            if ($busy < $cpu['max']){
                array_push($freeCpus, $cpu);
            }
        }

        return $freeCpus;
    }
}