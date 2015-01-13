<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Document\Job;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class JobService
{
    private $dm;
    private $repo;
    private $profileService;
    private $cpuService;

    public function __construct(DocumentManager $documentManager, ProfileService $profileService, CpuService $cpuService)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitEncoderBundle:Job');
        $this->profileService = $profileService;
        // TODO - necesario aqui?
        $this->cpuService = $cpuService;
    }

    /**
     * Add job
     */
    public function addJob($pathFile, $profile, $priority, $language = null, $description = array())
    {
        //- add job (path del archivo a transcodificar, perfil (nombre del perfil), prioridad, idioma = null, description (array internacionalizable) = array() ------ check archivo y perfil existentes, crear new job waiting, init timeini, persisitirlo

        if (!is_file($pathFile)) {
          throw new FileNotFoundException($pathFile); 
        }

        if (null === $this->profileService->getProfile($profile['name'])){
          // throw exception
        }
        
        $job = new Job();
        $job->setTimeini(new \DateTime('now'));
        $this->dm->persist($job);
        $this->dm->flush();

        // TODO - algo mas?
    }

    /**
     * Pause job
     *
     * Given an id, pauses the job only if it's waiting
     */
    public function pauseJob($id)
    {
        $job = $this->repo->find($id);

        if (null === $job){
          //throw exception
        }
        $this->changeStatus($job, Job::STATUS_WAITING, Job::STATUS_PAUSED);
    }

    /**
     * Resume Job
     *
     * Given an id, if the job status is waiting, pauses it
     */
    public function resumeJob($id)
    {
        $job = $this->repo->find($id);

        if (null === $job){
          //throw exception
        }
        $this->changeStatus($job, Job::STATUS_PAUSED, Job::STATUS_WAITING);      
    }

    /**
     * Cancel job
     *
     * Given an id, if the job status is paused or waiting, delete it. Throw exception otherwise
     */
    public function cancelJob($id)
    {
        $job = $this->repo->find($id);

        if (null === $job){
          //throw exception
        }
        if ((Job::STATUS_WAITING !== $job->getStatus()) && (Job::STATUS_PAUSED !== $job->getStatus())){
          //throw exception
        }
        $this->dm->remove($job);
        $this->dm->flush();         
    }

    /**
     * Get all jobs status
     */
    public function getAllJobsStatus()
    {
        return array(
                     'error' => count($this->repo->findWithStatus(array(Job::STATUS_ERROR))),
                     'paused' => count($this->repo->findWithStatus(array(Job::STATUS_PAUSED))),
                     'waiting' => count($this->repo->findWithStatus(array(Job::STATUS_WAITING))),
                     'executing' => count($this->repo->findWithStatus(array(Job::STATUS_EXECUTING))),
                     'finished' => count($this->repo->findWithStatus(array(Job::STATUS_FINISHED)))
                     );
    }
    
    /**
     * Get next job
     *
     * Returns the job in waiting status with higher priority (tie: timeini older)
     * Returns null otherwise
     * (TranscodingPeer::getNext())
     */
    public function getNextJob()
    {
        return $this->repo->findHigherPriorityWithStatus(array(Job::STATUS_WAITING));
    }

    /**
     * Change status of a given job
     */
    private function changeStatus(Job $job, $actualStatus, $newStatus)
    {
        if ($actualStatus === $job->getStatus()){
            $job->setStatus($newStatus);
            $this->dm->persist($job);
            $this->dm->flush();
        }
    }
}