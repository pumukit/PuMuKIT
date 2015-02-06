<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Executor\LocalExecutor;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\InspectionBundle\Services\InspectionServiceInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

//TODO add log
class JobService
{
    private $dm;
    private $repo;
    private $profileService;
    private $cpuService;
    private $inspectionService;
    private $tmp_path;
    private $test;

    public function __construct(DocumentManager $documentManager, ProfileService $profileService, CpuService $cpuService, InspectionServiceInterface $inspectionService, $tmp_path=null, $test=false)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitEncoderBundle:Job');
        $this->profileService = $profileService;
        $this->cpuService = $cpuService;
        $this->inspectionService = $inspectionService;
        $this->tmp_path = $tmp_path ? $tmp_path : sys_get_temp_dir();
        $this->test = $test;
    }

    /**
     * Add job
     */
    public function addJob($pathFile, $profile, $priority, MultimediaObject $multimediaObject, $language = null, $description = array())
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

        try{
            $duration = $this->inspectionService->getDuration($pathFile);
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
        
        $job = new Job();
        $job->setMmId($multimediaObject->getId());
        $job->setProfile($profile);
        $job->setPathIni($pathFile);
        $job->setDuration($duration);
        $job->setPriority($priority);
        if (null !== $language){
            //TODO languageId is only language "es", "en", "gl"
            $job->setLanguageId($language);
        }
        if (!empty($description)){
            $job->setI18nDescription($description);
        }
        $job->setTimeini(new \DateTime('now'));
        $this->dm->persist($job);
        $this->dm->flush();

        $this->setPathEndAndExtensions($job);

        $this->executeNextJob();

        return $job;
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

    public function deleteJob($id)
    {
        $job = $this->repo->find($id);

        if (null === $job){
            throw new \Exception("Can't find job with id ".$id);
        }
        if (Job::STATUS_ERROR !== $job->getStatus()){
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
                     'paused' => count($this->repo->findWithStatus(array(Job::STATUS_PAUSED))),
                     'waiting' => count($this->repo->findWithStatus(array(Job::STATUS_WAITING))),
                     'executing' => count($this->repo->findWithStatus(array(Job::STATUS_EXECUTING))),
                     'finished' => count($this->repo->findWithStatus(array(Job::STATUS_FINISHED))),
                     'error' => count($this->repo->findWithStatus(array(Job::STATUS_ERROR)))
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
    public function executeNextJob()
    {
        if($this->test){
            return null;
        }

        $freeCpu = $this->cpuService->getFreeCpu();
        $nextJob = $this->getNextJob();
        if (($freeCpu) && ($nextJob) && ($this->cpuService->isActive($freeCpu))){
            $nextJob->setCpu($freeCpu);
            $nextJob->setTimestart(new \DateTime('now'));
            $nextJob->setStatus(Job::STATUS_EXECUTING);
            $this->dm->persist($nextJob);
            $this->dm->flush();

            // TODO Define pumukit command and execute it in background
            // finalizado.php in Pumukit1.8
            $this->executeInBackground($nextJob);

            return $nextJob;
        }
        
        return null;
    }


    public function executeInBackground(Job $job)
    {

        $pb = new ProcessBuilder();
        // PHP wraps the process in "sh -c" by default, but we need to control
        // the process directly.
        /*
        if ( ! defined('PHP_WINDOWS_VERSION_MAJOR')) {
          $pb->add('exec');
        }
        */

        //TODO
        //$console = $this->getContainer()->getParameter('kernel.root_dir').'/console';
        $console = __DIR__ . '/../../../../app/console';

        $pb
          ->add('php')
          ->add($console);
        /*
        //TODO master_copy_h264 only works with --env=dev
          ->add('--env=prod')
          ;
        */

        if (false) {
          $pb->add('--verbose');
        }

        $pb
          ->add('pumukit:encoder:job')
          ->add($job->getId())
          ;

        $process = $pb->getProcess();

        $command = $process->getCommandLine();
        shell_exec("nohup $command 1> /dev/null 2> /dev/null & echo $!");

        //$process->disableOutput();
        //$process->start();
        //$process->run();
        //dump($process->getOutput());
        //dump($process->getErrorOutput());
        //dump($process->getCommandLine());
    }

    public function execute(Job $job)
    {
        $profile = $this->getProfile($job);
        $cpu = $this->cpuService->getCpuByName($job->getCpu());
        $commandLine = $this->renderBat($job);
        // TODO - LOG FILE

        // TODO - Set pathEnd in some point
        @mkdir(dirname($job->getPathEnd()), 0777, true);
        
        $executor = $this->getExecutor($profile['app'], $cpu['type']);
        
        try{
            $out = $executor->execute($commandLine);        
            $duration = $this->inspectionService->getDuration($job->getPathEnd());

            var_dump($commandLine);
            var_dump($profile['app']);
            var_dump($out);
            var_dump($job->getDuration());
            var_dump($duration);
    
            $job->setTimeend(new \DateTime('now'));
            $this->searchError($profile['app'], $out, $job->getDuration(), $duration);

            $job->setStatus(Job::STATUS_FINISHED);

            $this->createFile($job);
        }catch (\Exception $e){
            $job->setStatus(Job::STATUS_ERROR);
            var_dump("ERROR");
            var_dump($e->getMessage());            
        }

        $this->dm->persist($job);
        $this->dm->flush();

        $this->executeNextJob();
    }


    /**
     * Throw a exception if error executing the job.
     */
    public function searchError($profile, $var, $duration_in, $duration_end)
    {
        $duration_conf = 25;
        if (($duration_in < $duration_end - $duration_conf ) || ($duration_in > $duration_end + $duration_conf )){
            throw new \Exception();
        }
        return true;
    }

    /**
     * Get bat auto
     *
     * Generates execution line replacing %1 %2 %3 by
     * in, out and cfg files
     *
     * @return string commandLine
     */
    public function renderBat(Job $job)
    {
        $profile = $this->getProfile($job);

        $vars = array('{{input}}' => $job->getPathIni(), 
                      '{{output}}' => $job->getPathEnd());

        foreach(range(1, 9) as $identifier){
            $vars['{{temfile' . $identifier. '}}'] = $this->tmp_path . '/' . rand();
        }

        $commandLine = str_replace(array_keys($vars), array_values($vars), $profile['bat']);
    
        $cpu = $this->cpuService->getCpuByName($job->getCpu());
        if(CpuService::TYPE_WINDOWS === $cpu['type']){
            // TODO - PATH UNIX TRANSCODER and PATH WIN TRANSCODER
        }
        
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
        if (!file_exists($job->getPathIni())) {
            throw new \Exception('Error input file does not exist when setting the path_end');
        }

        if (!$job->getMmId()) {
            throw new \Exception('Error getting multimedia object to set path_end.');
        }
   
        if (!$job->getProfile()) {
            throw new \Exception('Error with profile name to set path_end.');
        }

        $profile = $this->getProfile($job);

        $extension = pathinfo($job->getPathIni(), PATHINFO_EXTENSION);
        $finalExtension = isset($profile['extension'])?$profile['extension']:$extension;

        $mmobj = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->find($job->getMmId());
        if (!$mmobj){
            throw new \Exception('Error getting multimedia object from id: '.$job->getMmId());
        }

        $tempDir = $profile['streamserver']['dir_out'] . '/' . $mmobj->getSeries()->getId();

        //TODO repeat mkdir (see this->execute)
        @mkdir($tempDir, 0777, true);

        $pathEnd = $tempDir.'/'.$job->getId().'.'.$finalExtension;
        $job->setPathEnd($pathEnd);
        $job->setExtIni($extension);
        $job->setExtEnd($finalExtension);

        $this->dm->persist($job);
        $this->dm->flush();
    }


    public function createFile($job)
    {

        $profile = $this->getProfile($job);

        $multimediaObject = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->find($job->getMmId());
        //TODO if mmobj doesn't exists
        $track = new Track();
        $track->addTag('profile:' . $job->getProfile());
        $track->setLanguage($job->getLanguageId());
        if(isset($profile['streamserver']['url_out'])) {
          $track->setUrl(str_replace($profile['streamserver']['dir_out'], $profile['streamserver']['url_out'], $job->getPathEnd()));
        }
        $track->setPath($job->getPathEnd());

        $this->inspectionService->autocompleteTrack($track);

        //TODO review
        $track->setOnlyAudio($track->getWidth() == 0);
        $track->setHide(false);

        $multimediaObject->addTrack($track);
     
        $this->dm->persist($multimediaObject);
        $this->dm->flush();
    }

    /**
     * Get jobs with multimedia object id
     *
     * @param string $mmId
     * @return ArrayCollection $jobs with mmId
     */
    public function getJobsByMultimediaObjectId($mmId)
    {
        return $this->repo->findByMultimediaObjectId($mmId);
    }

    /**
     * Get status error
     *
     * @return integer Job status error
     */
    public function getStatusError()
    {
        return Job::STATUS_ERROR;
    }

    /**
     * Retry job
     */
    public function retryJob($job)
    {
        if (Job::STATUS_ERROR === $job->getStatus()){
            return 'The job is right';
        }

        $profile = $this->getProfile($job);
        $tempDir = $profile['streamserver']['dir_out'] . '/' . $mmobj->getSeries()->getId();
        //TODO repeat mkdir (see this->execute)
        @mkdir($tempDir, 0777, true);

        $job->setStatus(Job::STATUS_WAITING);
        $job->setPriority(2);
        $job->setTimeIni(new \DateTime('now'));
        $this->dm->persist($job);
        $this->dm->flush();

        $this->execNext();

        return 'Retranscoding job';
    }

    private function getExecutor($app, $cpuType)
    {
        //TODO
        $executor = new LocalExecutor();
        return $executor;
    }

    private function getProfile($job)
    {
        return $this->profileService->getProfile($job->getProfile());
    }
}