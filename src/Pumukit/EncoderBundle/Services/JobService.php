<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Event\FileEvents;
use Pumukit\CoreBundle\Event\FileRemovedEvent;
use Pumukit\CoreBundle\Utils\SemaphoreUtils;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Event\EncoderEvents;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Executor\ExecutorException;
use Pumukit\EncoderBundle\Executor\LocalExecutor;
use Pumukit\EncoderBundle\Executor\RemoteHTTPExecutor;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\TrackService;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class JobService
{
    public const ADD_JOB_UNIQUE = 1;
    public const ADD_JOB_NOT_CHECKS = 2;

    private $dm;
    private $repo;
    private $profileService;
    private $cpuService;
    private $inspectionService;
    private $tmpPath;
    private $eventDispatcher;
    private $trackService;
    private $logger;
    private $environment;
    private $tokenStorage;
    private $propService;
    private $inboxPath;
    private $binPath;
    private $deleteInboxFiles;
    private $maxExecutionJobSeconds;

    public function __construct(
        DocumentManager $documentManager,
        ProfileService $profileService,
        CpuService $cpuService,
        InspectionFfprobeService $inspectionService,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        TrackService $trackService,
        TokenStorageInterface $tokenStorage,
        MultimediaObjectPropertyJobService $propService,
        $binPath,
        $environment = 'dev',
        $tmpPath = null,
        $inboxPath = null,
        $deleteInboxFiles = false,
        $maxExecutionJobSeconds = 43200
    ) {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository(Job::class);
        $this->profileService = $profileService;
        $this->cpuService = $cpuService;
        $this->inspectionService = $inspectionService;
        $this->tmpPath = $tmpPath ? realpath($tmpPath) : sys_get_temp_dir();
        $this->inboxPath = $inboxPath ? realpath($inboxPath) : sys_get_temp_dir();
        $this->logger = $logger;
        $this->trackService = $trackService;
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
        $this->environment = $environment;
        $this->propService = $propService;
        $this->binPath = $binPath;
        $this->deleteInboxFiles = $deleteInboxFiles;
        $this->maxExecutionJobSeconds = $maxExecutionJobSeconds;
    }

    public function createTrackFromLocalHardDrive(
        MultimediaObject $multimediaObject,
        UploadedFile $trackFile,
        $profile,
        $priority,
        $language,
        $description,
        $initVars = [],
        $duration = 0,
        $flags = 0
    ) {
        if (!$trackFile->isValid()) {
            throw new \Exception($trackFile->getErrorMessage());
        }

        if (!is_file($trackFile->getPathname())) {
            throw new FileNotFoundException($trackFile->getPathname());
        }

        $trackName = TextIndexUtils::cleanTextIndex(pathinfo($trackFile->getClientOriginalName())['filename']);

        $trackName = preg_replace('([^A-Za-z0-9])', '', $trackName);

        $pathFile = $trackFile->move(
            $this->tmpPath.'/'.$multimediaObject->getId(),
            $trackName.'.'.pathinfo($trackFile->getClientOriginalName())['extension']
        );

        if (!is_string($pathFile)) {
            $pathFile = $pathFile->getPathname();
        }
        $this->addJob(
            $pathFile,
            $profile,
            $priority,
            $multimediaObject,
            $language,
            $description,
            $initVars,
            $duration,
            $flags
        );

        return $multimediaObject;
    }

    public function createTrackFromInboxOnServer(
        MultimediaObject $multimediaObject,
        $trackUrl,
        $profile,
        $priority,
        $language,
        $description,
        $initVars = [],
        $duration = 0,
        $flags = 0
    ) {
        if (!is_file($trackUrl)) {
            throw new FileNotFoundException($trackUrl);
        }

        $this->addJob(
            $trackUrl,
            $profile,
            $priority,
            $multimediaObject,
            $language,
            $description,
            $initVars,
            $duration,
            $flags
        );

        return $multimediaObject;
    }

    /**
     * @deprecated use addJob with JobService::ADD_JOB_UNIQUE flag
     *
     * @param mixed      $pathFile
     * @param mixed      $profile
     * @param mixed      $priority
     * @param mixed|null $language
     * @param mixed      $description
     * @param mixed      $initVars
     */
    public function addUniqueJob(
        $pathFile,
        $profile,
        $priority,
        MultimediaObject $multimediaObject,
        $language = null,
        $description = [],
        $initVars = []
    ) {
        $this->addJob(
            $pathFile,
            $profile,
            $priority,
            $multimediaObject,
            $language,
            $description,
            $initVars,
            0,
            self::ADD_JOB_UNIQUE
        );
    }

    public function addJob(
        $pathFile,
        $profileName,
        $priority,
        MultimediaObject $multimediaObject,
        $language = null,
        $description = [],
        $initVars = [],
        $duration = 0,
        $flags = 0
    ) {
        if (self::ADD_JOB_UNIQUE && $flags) {
            $job = $this->repo->findOneBy(['profile' => $profileName, 'mm_id' => $multimediaObject->getId()]);

            if ($job) {
                return $job;
            }
        }

        if (null === $profile = $this->profileService->getProfile($profileName)) {
            $this->logger->error('[addJob] Can not find given profile with name "'.$profileName);

            throw new \Exception("Can't find given profile with name ".$profileName);
        }

        $checkduration = !(isset($profile['nocheckduration']) && $profile['nocheckduration']);

        if ($checkduration && !(self::ADD_JOB_NOT_CHECKS && $flags)) {
            if (!is_file($pathFile)) {
                $this->logger->error('[addJob] FileNotFoundException: Could not find file "'.$pathFile);

                throw new FileNotFoundException($pathFile);
            }
            $this->logger->info('Not doing duration checks on job with profile'.$profileName);

            try {
                $duration = $this->inspectionService->getDuration($pathFile);
            } catch (\Exception $e) {
                $this->logger->error('[addJob] InspectionService getDuration error message: '.$e->getMessage());

                throw new \Exception($e->getMessage());
            }

            if (0 == $duration) {
                $this->logger->error('[addJob] File duration is zero');

                throw new \Exception('File duration is zero');
            }
        }

        if ($checkduration && 0 == $duration) {
            throw new \Exception('The media file duration is zero');
        }

        $this->logger->info('[addJob] new Job');

        $job = new Job();
        $job->setMmId($multimediaObject->getId());
        $job->setProfile($profileName);
        $job->setPathIni($pathFile);
        $job->setDuration($duration);
        $job->setPriority($priority);
        $job->setInitVars($initVars);
        if (null !== $language) {
            $job->setLanguageId($language);
        }
        if (!empty($description)) {
            $job->setI18nDescription($description);
        }
        if ($email = $this->getUserEmail($job)) {
            $job->setEmail($email);
        }
        $job->setTimeini(new \DateTime('now'));
        $this->dm->persist($job);
        $this->dm->flush();

        $this->setPathEndAndExtensions($job);

        $this->logger->info('[addJob] Added job with id: '.$job->getId());
        $this->propService->addJob($multimediaObject, $job);

        $this->executeNextJob();

        return $job;
    }

    public function pauseJob($id)
    {
        $job = $this->repo->find($id);

        if (null === $job) {
            $this->logger->error('[pauseJob] Can not find job with id '.$id);

            throw new \Exception("Can't find job with id ".$id);
        }
        $this->changeStatus($job, Job::STATUS_WAITING, Job::STATUS_PAUSED);
    }

    public function resumeJob($id)
    {
        $job = $this->repo->find($id);

        if (null === $job) {
            $this->logger->error('[resumeJob] Can not find job with id '.$id);

            throw new \Exception("Can't find job with id ".$id);
        }
        $this->changeStatus($job, Job::STATUS_PAUSED, Job::STATUS_WAITING);
    }

    public function cancelJob($id)
    {
        $job = $this->repo->find($id);

        if (null === $job) {
            $this->logger->error('[cancelJob] Can not find job with id '.$id);

            throw new \Exception("Can't find job with id ".$id);
        }
        if ((Job::STATUS_WAITING !== $job->getStatus()) && (Job::STATUS_PAUSED !== $job->getStatus())) {
            $this->logger->error('[cancelJob] Trying to cancel job "'.$id.'" that is not paused or waiting');

            throw new \Exception('Trying to cancel job '.$id.' that is not paused or waiting');
        }
        $this->dm->remove($job);
        $this->dm->flush();
    }

    public function deleteJob($id)
    {
        $job = $this->repo->find($id);

        if (null === $job) {
            $this->logger->error('[deleteJob] Can not find job with id '.$id);

            throw new \Exception("Can't find job with id ".$id);
        }
        if (Job::STATUS_EXECUTING === $job->getStatus()) {
            $msg = sprintf(
                '[deleteJob] Trying to delete job "%s" that has executing status. Given status is %s',
                $id,
                $job->getStatus()
            );
            $this->logger->error($msg);

            throw new \Exception($msg);
        }
        $this->dm->remove($job);
        $this->dm->flush();
    }

    public function updateJobPriority($id, $priority)
    {
        $job = $this->repo->find($id);

        if (null === $job) {
            $this->logger->error('[updateJobPriority] Can not find job with id '.$id);

            throw new \Exception("Can't find job with id ".$id);
        }

        $job->setPriority($priority);
        $this->dm->persist($job);
        $this->dm->flush();
    }

    public function getAllJobsStatus()
    {
        return [
            'paused' => $this->repo->countWithStatus([Job::STATUS_PAUSED]),
            'waiting' => $this->repo->countWithStatus([Job::STATUS_WAITING]),
            'executing' => $this->repo->countWithStatus([Job::STATUS_EXECUTING]),
            'finished' => $this->repo->countWithStatus([Job::STATUS_FINISHED]),
            'error' => $this->repo->countWithStatus([Job::STATUS_ERROR]),
        ];
    }

    public function getAllJobsStatusWithOwner($owner)
    {
        return [
            'paused' => is_countable($this->repo->findWithStatusAndOwner([Job::STATUS_PAUSED], [], $owner)) ? count($this->repo->findWithStatusAndOwner([Job::STATUS_PAUSED], [], $owner)) : 0,
            'waiting' => is_countable($this->repo->findWithStatusAndOwner([Job::STATUS_WAITING], [], $owner)) ? count($this->repo->findWithStatusAndOwner([Job::STATUS_WAITING], [], $owner)) : 0,
            'executing' => is_countable($this->repo->findWithStatusAndOwner([Job::STATUS_EXECUTING], [], $owner)) ? count($this->repo->findWithStatusAndOwner([Job::STATUS_EXECUTING], [], $owner)) : 0,
            'finished' => is_countable($this->repo->findWithStatusAndOwner([Job::STATUS_FINISHED], [], $owner)) ? count($this->repo->findWithStatusAndOwner([Job::STATUS_FINISHED], [], $owner)) : 0,
            'error' => is_countable($this->repo->findWithStatusAndOwner([Job::STATUS_ERROR], [], $owner)) ? count($this->repo->findWithStatusAndOwner([Job::STATUS_ERROR], [], $owner)) : 0,
        ];
    }

    public function getNextJob()
    {
        return $this->repo->findHigherPriorityWithStatus([Job::STATUS_WAITING]);
    }

    public function executeNextJob()
    {
        if ('test' == $this->environment) {
            return null;
        }

        $this->checkService();
        $nextJobToExecute = null;

        $semaphore = SemaphoreUtils::acquire(1000003);

        $nextJob = $this->getNextJob();
        if (!isset($nextJob)) {
            SemaphoreUtils::release($semaphore);

            return null;
        }
        $profile = $nextJob->getProfile();
        $freeCpu = $this->cpuService->getFreeCpu($profile);
        if ($freeCpu && $nextJob && $this->cpuService->isActive($freeCpu)) {
            $nextJob->setCpu($freeCpu);
            $nextJob->setTimestart(new \DateTime('now'));
            $nextJob->setStatus(Job::STATUS_EXECUTING);
            $this->dm->persist($nextJob);
            $this->dm->flush();
            $this->executeInBackground($nextJob);

            $nextJobToExecute = $nextJob;
        }

        SemaphoreUtils::release($semaphore);

        return $nextJobToExecute;
    }

    public function executeInBackground(Job $job): void
    {
        $multimediaObject = $this->getMultimediaObject($job);
        $this->propService->setJobAsExecuting($multimediaObject, $job);

        $command = [
            'php',
            "{$this->binPath}/console",
            sprintf('--env=%s', $this->environment),
            'pumukit:encoder:job',
            $job->getId(),
        ];

        $process = new Process($command);

        $command = $process->getCommandLine();
        $this->logger->info('[executeInBackground] CommandLine '.$command);
        shell_exec("nohup {$command} 1> /dev/null 2> /dev/null & echo $!");
    }

    public function execute(Job $job)
    {
        set_time_limit(0);

        $this->checkService();

        $profile = $this->getProfile($job);
        $cpu = $this->cpuService->getCpuByName($job->getCpu());
        $commandLine = $this->renderBat($job);

        $executor = $this->getExecutor($cpu);

        try {
            $this->mkdir(dirname($job->getPathEnd()));

            // Throws exception when the multimedia object is not found.
            $multimediaObject = $this->getMultimediaObject($job);
            $this->propService->setJobAsExecuting($multimediaObject, $job);
            // Executes the job. It can throw exceptions if the executor has issues.
            $out = $executor->execute($commandLine, $cpu);
            $job->setOutput($out);
            // Throws exception if the video does not exist or does not have video/audio tracks.
            $duration = $this->inspectionService->getDuration($job->getPathEnd());
            $job->setNewDuration($duration);

            $this->logger->info('[execute] cpu: '.serialize($cpu));
            $this->logger->info('[execute] CommandLine: '.$commandLine);
            $this->logger->info('[execute] profile.app: "'.$profile['app'].'"');
            $this->logger->info('[execute] out: "'.$out.'"');
            $this->logger->info('[execute] job duration: '.$job->getDuration());
            $this->logger->info('[execute] duration: '.$duration);

            // Check for different durations. Throws exception if they don't match.
            $this->searchError($profile, $job->getDuration(), $duration);

            $job->setTimeend(new \DateTime('now'));
            $job->setStatus(Job::STATUS_FINISHED);

            $multimediaObject = $this->getMultimediaObject($job); // Necesary to refresh the document
            $this->dm->refresh($multimediaObject);

            $track = $this->createTrackWithJob($job);
            $this->dispatch(true, $job, $track);

            $this->propService->finishJob($multimediaObject, $job);

            $this->deleteTempFiles($job);
        } catch (\Exception $e) {
            $job->setTimeend(new \DateTime('now'));
            $job->setStatus(Job::STATUS_ERROR);

            $job->appendOutput($e->getMessage());
            $this->logger->error('[execute] error job output: '.$e->getMessage());
            $this->dispatch(false, $job);

            $multimediaObject = $this->getMultimediaObject($job);  // Necesary to refresh the document
            $this->propService->errorJob($multimediaObject, $job);
            // If the transco is disconnected or there is an authentication issue, we don't want to send more petitions to this transco.
            if ($e instanceof ExecutorException && 'prod' == $this->environment) {
                $cpuName = $job->getCpu();
                $this->cpuService->activateMaintenance($cpuName);
            }
        }

        $this->dm->persist($job);
        $this->dm->flush();

        $this->executeNextJob();
    }

    public function searchError($profile, $durationIn, $durationEnd)
    {
        // This allows to configure a profile for videos without timestamps to be reindexed.
        if (isset($profile['nocheckduration']) && $profile['nocheckduration']) {
            return true;
        }

        $duration_conf = 25;
        if (($durationIn < $durationEnd - $duration_conf) || ($durationIn > $durationEnd + $duration_conf)) {
            throw new \Exception(
                sprintf('Final duration (%s) and initial duration (%s) are differents', $durationEnd, $durationIn)
            );
        }

        return true;
    }

    public function renderBat(Job $job)
    {
        $profile = $this->getProfile($job);
        $mmobj = $this->getMultimediaObject($job);

        $vars = $job->getInitVars();
        if (!is_array($vars)) {
            $vars = [];
        }

        $vars['tracks'] = [];
        $vars['tracks_audio'] = [];
        $vars['tracks_video'] = [];
        foreach ($mmobj->getTracks() as $track) {
            foreach ($track->getTags() as $tag) {
                $vars['tracks'][$tag] = $track->getPath();
                if ($track->isOnlyAudio()) {
                    $vars['tracks_audio'][$tag] = $track->getPath();
                } else {
                    $vars['tracks_video'][$tag] = $track->getPath();
                }
            }
        }

        $vars['properties'] = $mmobj->getProperties();

        $vars['input'] = $job->getPathIni();
        $vars['output'] = $job->getPathEnd();

        foreach (range(1, 9) as $identifier) {
            $vars['tmpfile'.$identifier] = $this->tmpPath.'/'.random_int(0, mt_getrandmax());
        }

        $loader = new ArrayLoader(['bat' => $profile['bat']]);
        $twig = new Environment($loader);

        $commandLine = $twig->render('bat', $vars);
        $this->logger->info('[renderBat] CommandLine: '.$commandLine);

        $cpu = $this->cpuService->getCpuByName($job->getCpu());
        if (CpuService::TYPE_WINDOWS === $cpu['type']) {
        }

        return $commandLine;
    }

    public function setPathEndAndExtensions(Job $job)
    {
        if (!file_exists($job->getPathIni())) {
            $this->logger->error('[setPathEndAndExtensions] Error input file does not exist when setting the path_end');

            throw new \Exception('Error input file does not exist when setting the path_end');
        }

        if (!$job->getMmId()) {
            $this->logger->error('[setPathEndAndExtensions] Error getting multimedia object to set path_end.');

            throw new \Exception('Error getting multimedia object to set path_end.');
        }

        if (!$job->getProfile()) {
            $this->logger->error('[setPathEndAndExtensions] Error with profile name to set path_end.');

            throw new \Exception('Error with profile name to set path_end.');
        }

        $profile = $this->getProfile($job);
        $mmobj = $this->getMultimediaObject($job);

        $extension = pathinfo($job->getPathIni(), PATHINFO_EXTENSION);
        $pathEnd = $this->getPathEnd($profile, $mmobj->getSeries()->getId(), $job->getId(), $extension);

        $job->setPathEnd($pathEnd);
        $job->setExtIni($extension);
        $job->setExtEnd(pathinfo($pathEnd, PATHINFO_EXTENSION));

        $this->dm->persist($job);
        $this->dm->flush();
    }

    public function createTrackWithJob(Job $job)
    {
        $this->logger->info('Create new track with job '.$job->getId().' and profileName '.$job->getProfile());

        $multimediaObject = $this->getMultimediaObject($job);

        return $this->createTrack(
            $multimediaObject,
            $job->getPathEnd(),
            $job->getProfile(),
            $job->getLanguageId(),
            $job->getI18nDescription(),
            $job->getPathIni()
        );
    }

    public function createTrackWithFile(
        $pathFile,
        $profileName,
        MultimediaObject $multimediaObject,
        $language = null,
        $description = []
    ) {
        $this->logger->info('Create new track with file '.$pathFile.' and profileName '.$profileName);

        $profile = $this->profileService->getProfile($profileName);

        $pathEnd = $this->getPathEnd(
            $profile,
            $multimediaObject->getSeries()->getId(),
            pathinfo($pathFile, PATHINFO_FILENAME),
            pathinfo($pathFile, PATHINFO_EXTENSION)
        );

        if (!copy($pathFile, $pathEnd)) {
            throw new \Exception('Error to copy file');
        }

        return $this->createTrack($multimediaObject, $pathEnd, $profileName, $language, $description, $pathFile);
    }

    public function createTrack(
        MultimediaObject $multimediaObject,
        $pathEnd,
        $profileName,
        $language = null,
        $description = [],
        $pathFile = null
    ) {
        $profile = $this->profileService->getProfile($profileName);

        $track = new Track();
        if ($pathFile && $profile['master']) {
            $pathInfo = pathinfo($pathFile, PATHINFO_BASENAME);
            $track->setOriginalName($pathInfo);
        }

        $track->addTag('profile:'.$profileName);
        if ($profile['master']) {
            $track->addTag('master');
        }
        if ($profile['downloadable']) {
            $track->setAllowDownload(true);
        }
        if ($profile['display']) {
            $track->addTag('display');
        }
        foreach (array_filter(preg_split('/[,\s]+/', $profile['tags'])) as $tag) {
            $track->addTag(trim($tag));
        }

        if (!empty($description)) {
            $track->setI18nDescription($description);
        }
        if ($language) {
            $track->setLanguage($language);
        }

        $track->setPath($pathEnd);
        if (isset($profile['streamserver']['url_out'])) {
            $track->setUrl(
                str_replace(
                    realpath($profile['streamserver']['dir_out']),
                    $profile['streamserver']['url_out'],
                    $pathEnd
                )
            );
        }

        $this->inspectionService->autocompleteTrack($track);

        $track->setOnlyAudio(0 == $track->getWidth());
        $track->setHide(!$profile['display']);

        $multimediaObject->setDuration($track->getDuration());

        $this->trackService->addTrackToMultimediaObject($multimediaObject, $track);

        return $track;
    }

    public function getNotFinishedJobsByMultimediaObjectId($mmId)
    {
        return $this->repo->findNotFinishedByMultimediaObjectId($mmId);
    }

    public function getStatusError()
    {
        return Job::STATUS_ERROR;
    }

    public function retryJob(Job $job)
    {
        if (Job::STATUS_ERROR !== $job->getStatus()) {
            return false;
        }

        $mmobj = $this->dm->getRepository(MultimediaObject::class)->find($job->getMmId());

        $profile = $this->getProfile($job);
        $tempDir = $profile['streamserver']['dir_out'].'/'.$mmobj->getSeries()->getId();

        $this->mkdir($tempDir);

        $job->setStatus(Job::STATUS_WAITING);
        $job->setPriority(2);
        $job->setTimeIni(new \DateTime('now'));
        $this->dm->persist($job);
        $this->dm->flush();

        $this->propService->retryJob($mmobj, $job);

        $this->executeNextJob();

        return true;
    }

    public function checkService()
    {
        $existsJobsToUpdate = false;
        $jobs = $this->repo->findWithStatus([Job::STATUS_EXECUTING]);
        $nowDateTime = new \DateTimeImmutable();

        foreach ($jobs as $job) {
            if (!$job->getTimestart()) {
                continue;
            }
            $maxExecutionJobTime = clone $job->getTimestart();
            $maxExecutionJobTime->add(new \DateInterval('PT'.$this->maxExecutionJobSeconds.'S'));
            if ($nowDateTime > $maxExecutionJobTime) {
                $job->setStatus(Job::STATUS_ERROR);
                $message = '[checkService] Job executing for a long time, set status to ERROR. Máx execution time was '.
                           $maxExecutionJobTime->format('Y-m-d H:i:s');
                $job->appendOutput($message);
                $this->logger->error($message.' for JOB ID '.$job->getId());

                $existsJobsToUpdate = true;
                $this->dispatch(false, $job);
            }
        }

        if ($existsJobsToUpdate) {
            $this->dm->flush();
        }
    }

    public function removeTrack(MultimediaObject $multimediaObject, string $trackId): MultimediaObject
    {
        return $this->trackService->removeTrackFromMultimediaObject($multimediaObject, $trackId);
    }

    private function deleteTempFiles(Job $job)
    {
        if (false !== strpos($job->getPathIni(), (string) $this->tmpPath)) {
            unlink($job->getPathIni());
        } elseif ($this->deleteInboxFiles && false !== strpos($job->getPathIni(), (string) $this->inboxPath)) {
            unlink($job->getPathIni());

            $event = new FileRemovedEvent($job->getPathIni());
            $this->eventDispatcher->dispatch($event, FileEvents::FILE_REMOVED);
        }
    }

    private function changeStatus(Job $job, $actualStatus, $newStatus)
    {
        if ($actualStatus === $job->getStatus()) {
            $job->setStatus($newStatus);
            $this->dm->persist($job);
            $this->dm->flush();
        }
    }

    private function getPathEnd(array $profile, $dir, $file, $extension)
    {
        $finalExtension = $profile['extension'] ?? $extension;

        $tempDir = $profile['streamserver']['dir_out'].'/'.$dir;

        $this->mkdir($tempDir);

        return realpath($tempDir).'/'.$file.'.'.$finalExtension;
    }

    private function getExecutor($cpu)
    {
        $localhost = ['localhost', '127.0.0.1'];

        return (in_array($cpu['host'], $localhost)) ? new LocalExecutor() : new RemoteHTTPExecutor();
    }

    private function getProfile(Job $job)
    {
        $profile = $this->profileService->getProfile($job->getProfile());

        if (!$profile) {
            $errorMsg = sprintf(
                '[createTrackWithJob] Profile %s not found when the job %s creates the track',
                $job->getProfile(),
                $job->getId()
            );
            $this->logger->error($errorMsg);

            throw new \Exception($errorMsg);
        }

        return $profile;
    }

    private function getMultimediaObject(Job $job)
    {
        $multimediaObject = $this->dm->getRepository(MultimediaObject::class)->find($job->getMmId());

        if (!$multimediaObject) {
            $errorMsg = sprintf(
                '[createTrackWithJob] Multimedia object %s not found when the job %s creates the track',
                $job->getMmId(),
                $job->getId()
            );
            $this->logger->error($errorMsg);

            throw new \Exception($errorMsg);
        }

        return $multimediaObject;
    }

    private function dispatch($success, Job $job, Track $track = null)
    {
        $multimediaObject = $this->getMultimediaObject($job);

        $event = new JobEvent($job, $track, $multimediaObject);
        $this->eventDispatcher->dispatch($event, $success ? EncoderEvents::JOB_SUCCESS : EncoderEvents::JOB_ERROR);
    }

    private function getUserEmail(Job $job = null)
    {
        if (null !== $token = $this->tokenStorage->getToken()) {
            if (($user = $token->getUser()) instanceof User) {
                return $user->getEmail();
            }
        }

        if ($job) {
            $otherJob = $this->repo->findOneBy(
                ['mm_id' => $job->getMmId(), 'email' => ['$exists' => true]],
                ['timeini' => 1]
            );
            if ($otherJob && $otherJob->getEmail()) {
                return $otherJob->getEmail();
            }
        }

        return null;
    }

    private function mkdir(string $path)
    {
        $fs = new Filesystem();
        $fs->mkdir($path);
    }
}
