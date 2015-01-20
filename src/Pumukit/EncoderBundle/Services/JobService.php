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
        $this->cpuService = $cpuService;
    }

    /**
     * Add job
     */
    public function addJob($pathFile, $profile, $priority, $multimediaObject, $language = null, $description = array())
    {
        if (!is_file($pathFile)) {
            throw new FileNotFoundException($pathFile); 
        }

        if (null === $this->profileService->getProfile($profile)){
            throw new \Exception("Can't find given profile with name ".$profile);
        }
        
        if (null === $multimediaObject){
            throw new \Exception("Given null multimedia object");
        }
        
        $job = new Job();
        $job->setMmId($multimediaObject->getId());
        $job->setProfile($profile);
        $job->setPathIni($pathFile);
        //$job->setDuration($pathFile);
        $job->setPriority($priority);
        if (null !== $language){
            $job->setLanguageId($language);
        }
        if (!empty($description)){
            // TODO - DEFINE SET DESCRIPTION (i18n)
            //$job->setDescription($description);
        }
        $job->setTimeini(new \DateTime('now'));
        $this->dm->persist($job);
        $this->dm->flush();
        $this->setPathEndAndExtensions($job);
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
            throw new \Exception("Can't find job with id ".$id);
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
            throw new \Exception("Can't find job with id ".$id);
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
            throw new \Exception("Can't find job with id ".$id);
        }
        if ((Job::STATUS_WAITING !== $job->getStatus()) && (Job::STATUS_PAUSED !== $job->getStatus())){
            throw new \Exception("Trying to cancel job ".$id." that is not paused or waiting");
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
     */
    public function getNextJob()
    {
        return $this->repo->findHigherPriorityWithStatus(array(Job::STATUS_WAITING));
    }

    /**
     * Exec next job
     */
    public function execNextJob()
    {
        $freeCpu = $this->cpuService->getFreeCpu();
        $nextJob = $this->getNextJob();
        if (($freeCpu) && ($nextJob) && ($this->cpuService->isActive($freeCpu))){
            $nextJob->setCpu($cpu['name']);
            $nextJob->setTimestart(new \DateTime('now'));
            $nextJob->setStatus(Job::STATUS_EXECUTING);
            $this->dm->persist($nextJob);
            $this->dm->flush();

            // TODO Define pumukit command and execute it in background
            // finalizado.php in Pumukit1.8
            $this->execFinalizado($nextJob);

            return $nextJob;
        }
        
        return null;
    }

    /**
     * Exec finalizado -> pasarlo a comando
     *
     * Para ejecutar en background
     */
    public function execFinalizado(Job $job)
    {
        $avsFile = null;
        $commandLine = $this->getBatAuto($job);
        // TODO - LOG FILE

        // TODO - Set pathEnd in some point
        @mkdir(dirname($job->getPathEnd()));
        
        // TODO - Create Pumukit command with this and execute it in background
        $cpu = $this->cpuService->getCpuByName($job->getCpu());
        $ch = curl_init('http://'.$cpu['host'].'/webserver.php'); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic ".base64_encode($cpu['user'].':'.$cpu['password'])));
        curl_setopt ($ch, CURLOPT_POST, 1);
        // TODO - nombre 'ruta'
        curl_setopt ($ch, CURLOPT_POSTFIELDS, "ruta=$commandLine");
        $var = curl_exec($ch); 
        $error = curl_error($ch);
        ////////////////////////////////////////////////////////////////////////
        
        try{
            // TODO with Inspection Bundle
            //$duration = Track::getDuration($job->getPathEnd());
        }catch (\Exception $e){
            $duration = 0;
        }

        $profile = $this->profileService->getProfile($job->getProfile());
        if ($this->searchError($profile['app'], $var, $job->getDuration(), $duration)){


        }


    }

    /**
     * Get bat auto
     *
     * Generates execution line replacing %1 %2 %3 by
     * in, out and cfg files
     *
     * @return string commandLine
     */
    public function getBatAuto(Job $job)
    {
        $profile = $this->profileService->getProfile($job->getProfile());

        $commandLine = $profile['bat'];
        $commandLine = str_replace('%1', $job->getPathIni(), $commandLine);
        $commandLine = str_replace('%2', $job->getPathEnd(), $commandLine);

        foreach(range(1, 9) as $identifier){
            do{
                //$myTmpFile = sfConfig::get('app_transcoder_path_tmp').'/'. rand() ;
                // TODO - TEMP PATH TRANSCODER
                $tmpPathTranscoder = '/tmp/path/transcoder';
                $myTmpFile = $tmpPathTranscoder.'/'.rand();
            } while (file_exists($myTmpFile));
            $commandLine = str_replace('%_' . $identifier, $myTmpFile, $commandLine);
        }
    
        $cpu = $this->cpuService->getCpuByName($job->getCpu());
        if(CpuService::TYPE_WINDOWS === $cpu['type']){
            // TODO - PATH UNIX TRANSCODER and PATH WIN TRANSCODER
            //$commandLine = str_replace(sfConfig::get('app_transcoder_path_unix'), sfConfig::get('app_transcoder_path_win'), $commandLine); 
            $commandLine = str_replace('/tmp/path/unix', '/tmp/path/windows', $commandLine);
            // TODO - ¿----antes-----?
            $commandLine = str_replace("\\/","----antes----",$commandLine);
            $commandLine = str_replace("/","\\",$commandLine);
            $commandLine = str_replace("----antes----", "/",$commandLine);
            $commandLine = str_replace(" \\i "," /i ",$commandLine); // TODO ? CAMBIAR FORMA de hacerlo
        }
    
        $commandLine = urlencode($commandLine);
        $commandLine .= " \n";
    
        return $commandLine;
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
    
    /**
     * Set path end auto
     */
    public function setPathEndAndExtensions($job)
    {
        if ((0 === strlen($job->getPathIni())) || (is_null($job->getMmId())) || (is_null($job->getProfile()))){
            throw new \Exception('Error in path, multimedia object id or profile name');
        }

        $profile = $this->profileService->getProfile($job->getProfile());

        $extension = pathinfo($job->getPathIni(), PATHINFO_EXTENSION);
        $finalExtension = ($profile['extension']?$profile['extension']:$extension);

        $mmobj = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->find($job->getMmId());
        $tempDir = $profile['streamserver']['dir_out'].'/'.$mmobj->getSeries()->getId();
        @mkdir($tempDir, 0777, true);

        $pathEnd = $tempDir.'/'.$job->getId().'.'.$finalExtension;
        $job->setPathEnd($pathEnd);
        $job->setExtIni($extension);
        $job->setExtEnd($finalExtension);

        $this->dm->persist($job);
        $this->dm->flush();
    }
}