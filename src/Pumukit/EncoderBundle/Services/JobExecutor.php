<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Utils\SemaphoreUtils;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Event\EncoderEvents;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Executor\ExecutorException;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;

final class JobExecutor
{
    protected const EXECUTE_COMMAND = 'pumukit:encoder:job';
    private DocumentManager $documentManager;
    private CpuService $cpuService;
    private MultimediaObjectPropertyJobService $multimediaObjectPropertyJobService;
    private JobDispatcher $jobDispatcher;
    private LoggerInterface $logger;
    private string $binPath;
    private string $environment;
    private int $maxExecutionJobSeconds;

    public function __construct(
        DocumentManager $documentManager,
        CpuService $cpuService,
        MultimediaObjectPropertyJobService $multimediaObjectPropertyJobService,
        JobDispatcher $jobDispatcher,
        LoggerInterface $logger,
        string $binPath,
        string $environment,
        int $maxExecutionJobSeconds = 43200
    ) {
        $this->documentManager = $documentManager;
        $this->cpuService = $cpuService;
        $this->multimediaObjectPropertyJobService = $multimediaObjectPropertyJobService;
        $this->jobDispatcher = $jobDispatcher;
        $this->logger = $logger;
        $this->binPath = $binPath;
        $this->environment = $environment;
        $this->maxExecutionJobSeconds = $maxExecutionJobSeconds;
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
            $this->documentManager->flush();
            $this->executeInBackground($nextJob);

            $nextJobToExecute = $nextJob;
        }

        SemaphoreUtils::release($semaphore);

        return $nextJobToExecute;
    }

    private function getNextJob()
    {
        return $this->documentManager->getRepository(Job::class)->findHigherPriorityWithStatus([Job::STATUS_WAITING]);
    }


    public function execute(Job $job): void
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

            $multimediaObject = $this->getMultimediaObject($job); // Necessary to refresh the document
            $this->documentManager->refresh($multimediaObject);

            $track = $this->createTrackWithJob($job);
            $this->jobDispatcher->dispatch(true, $job, $track);
            //$this->dispatch(true, $job, $track);

            $this->propService->finishJob($multimediaObject, $job);

            $this->deleteTempFiles($job);
        } catch (\Exception $e) {
            $job->setTimeend(new \DateTime('now'));
            $job->setStatus(Job::STATUS_ERROR);

            $job->appendOutput($e->getMessage());
            $this->logger->error('[execute] error job output: '.$e->getMessage());
            $this->jobDispatcher->dispatch(false, $job);
//            $this->dispatch(false, $job);

            $multimediaObject = $this->getMultimediaObject($job);  // Necessary to refresh the document
            $this->propService->errorJob($multimediaObject, $job);
            // If the transco is disconnected or there is an authentication issue, we don't want to send more petitions to this transco.
            if ($e instanceof ExecutorException && 'prod' == $this->environment) {
                $cpuName = $job->getCpu();
                $this->cpuService->activateMaintenance($cpuName);
            }
        }

        $this->dm->flush();

        $this->executeNextJob();
    }

    private function executeInBackground(Job $job): void
    {
        $multimediaObject = $this->getMultimediaObject($job);
        $this->multimediaObjectPropertyJobService->setJobAsExecuting($multimediaObject, $job);

        $command = [
            'php',
            "{$this->binPath}/console",
            sprintf('--env=%s', $this->environment),
            self::EXECUTE_COMMAND,
            $job->getId(),
        ];

        $process = new Process($command);

        $command = $process->getCommandLine();
        $this->logger->info('[executeInBackground] CommandLine '.$command);
        shell_exec("nohup {$command} 1> /dev/null 2> /dev/null & echo $!");
    }

    private function getMultimediaObject(Job $job): MultimediaObject
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->find($job->getMmId());

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

    private function checkService(): void
    {
        $existsJobsToUpdate = false;
        $jobs = $this->documentManager->getRepository(Job::class)->findWithStatus([Job::STATUS_EXECUTING]);
        $nowDateTime = new \DateTimeImmutable();

        foreach ($jobs as $job) {
            $maxExecutionJobTime = clone $job->getTimestart();
            $maxExecutionJobTime->add(new \DateInterval('PT'.$this->maxExecutionJobSeconds.'S'));
            if ($nowDateTime > $maxExecutionJobTime) {
                $job->setStatus(Job::STATUS_ERROR);
                $message = '[checkService] Job executing for a long time, set status to ERROR. Máx execution time was '.
                    $maxExecutionJobTime->format('Y-m-d H:i:s');
                $job->appendOutput($message);
                $this->logger->error($message.' for JOB ID '.$job->getId());

                $existsJobsToUpdate = true;
                $this->jobDispatcher->dispatch(false, $job);
//                $this->dispatch(false, $job);
            }
        }

        if ($existsJobsToUpdate) {
            $this->documentManager->flush();
        }
    }

    private function dispatch($success, Job $job, Track $track = null): void
    {
        $multimediaObject = $this->getMultimediaObject($job);

        $event = new JobEvent($job, $track, $multimediaObject);
        $this->eventDispatcher->dispatch($event, $success ? EncoderEvents::JOB_SUCCESS : EncoderEvents::JOB_ERROR);
    }

}
