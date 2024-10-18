<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Utils\FileSystemUtils;
use Pumukit\CoreBundle\Utils\SemaphoreUtils;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Event\EncoderEvents;
use Pumukit\EncoderBundle\Executor\ExecutorException;
use Pumukit\EncoderBundle\Executor\ExecutorInterface;
use Pumukit\EncoderBundle\Executor\LocalExecutor;
use Pumukit\EncoderBundle\Executor\RemoteHTTPExecutor;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Services\MediaCreator;
use Symfony\Component\Process\Process;

final class JobExecutor
{
    private const EXECUTE_COMMAND = 'pumukit:encoder:job';
    private DocumentManager $documentManager;
    private CpuService $cpuService;
    private MultimediaObjectPropertyJobService $multimediaObjectPropertyJobService;
    private JobDispatcher $jobDispatcher;
    private LoggerInterface $logger;
    private string $binPath;
    private string $environment;
    private int $maxExecutionJobSeconds;
    private InspectionFfprobeService $inspectionService;
    private JobValidator $jobValidator;
    private ProfileValidator $profileValidator;
    private MediaCreator $mediaCreator;
    private JobRemover $jobRemover;
    private JobRender $jobRender;

    public function __construct(
        DocumentManager $documentManager,
        CpuService $cpuService,
        JobValidator $jobValidator,
        ProfileValidator $profileValidator,
        MediaCreator $mediaCreator,
        JobRemover $jobRemover,
        JobRender $jobRender,
        MultimediaObjectPropertyJobService $multimediaObjectPropertyJobService,
        InspectionFfprobeService $inspectionService,
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
        $this->inspectionService = $inspectionService;
        $this->jobValidator = $jobValidator;
        $this->profileValidator = $profileValidator;
        $this->mediaCreator = $mediaCreator;
        $this->jobRemover = $jobRemover;
        $this->jobRender = $jobRender;
    }

    public function executeNextJob()
    {
        if ('test' === $this->environment) {
            return null;
        }

        $this->checkExecutingJobs();
        $nextJobToExecute = null;

        $semaphore = SemaphoreUtils::acquire(1000003);

        $nextJob = $this->getNextJob();
        if (!isset($nextJob)) {
            SemaphoreUtils::release($semaphore);

            return null;
        }
        $profile = $nextJob->getProfile();
        $freeCpu = $this->cpuService->getFreeCpu($profile);
        if ($freeCpu && $this->cpuService->isActive($freeCpu)) {
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

    public function execute(Job $job): void
    {
        set_time_limit(0);

        $this->checkExecutingJobs();

        $profile = $this->profileValidator->ensureProfileExists($job->getProfile());
        $cpu = $this->cpuService->getCpuByName($job->getCpu());
        $commandLine = $this->jobRender->renderBat($job);

        $executor = $this->getExecutor($cpu);

        try {
            FileSystemUtils::createFolder(dirname($job->getPathEnd()));
            // $this->mkdir(dirname($job->getPathEnd()));

            // Throws exception when the multimedia object is not found.
            $multimediaObject = $this->jobValidator->ensureMultimediaObjectExists($job);
            $this->multimediaObjectPropertyJobService->setJobAsExecuting($multimediaObject, $job);
            // Executes the job. It can throw exceptions if the executor has issues.
            $out = $executor->execute($commandLine, $cpu);
            $job->setOutput($out);
            // Throws exception if the video does not exist or does not have video/audio tracks.

            if ($multimediaObject->isVideoAudioType()) {
                $duration = $this->inspectionService->getDuration($job->getPathEnd());
                $job->setNewDuration($duration);
                // Check for different durations. Throws exception if they don't match.
                $this->jobValidator->searchError($profile, $job->getDuration(), $duration);
            }

            $this->logger->info('[execute] cpu: '.serialize($cpu));
            $this->logger->info('[execute] CommandLine: '.$commandLine);
            $this->logger->info('[execute] out: "'.$out.'"');

            $job->setTimeend(new \DateTime('now'));
            $job->setStatus(Job::STATUS_FINISHED);

            $multimediaObject = $this->jobValidator->ensureMultimediaObjectExists($job); // Necessary to refresh the document
            $this->documentManager->refresh($multimediaObject);

            $track = $this->createMediaWithJob($job);
            $this->jobDispatcher->dispatch(EncoderEvents::JOB_SUCCESS, $job, $track);
            $this->multimediaObjectPropertyJobService->finishJob($multimediaObject, $job);
            $this->jobRemover->deleteTempFilesFromJob($job);
        } catch (\Exception $e) {
            $this->logger->error('[execute] error job output: '.$e->getTraceAsString());

            $job->setTimeend(new \DateTime('now'));
            $job->setStatus(Job::STATUS_ERROR);
            $job->appendOutput($e->getMessage());

            $this->jobDispatcher->dispatch(EncoderEvents::JOB_ERROR, $job);

            $multimediaObject = $this->jobValidator->ensureMultimediaObjectExists($job);  // Necessary to refresh the document
            $this->multimediaObjectPropertyJobService->errorJob($multimediaObject, $job);
            // If the transco is disconnected or there is an authentication issue, we don't want to send more petitions to this transco.
            if ($e instanceof ExecutorException && 'prod' == $this->environment) {
                $this->cpuService->activateMaintenance($job->getCpu());
            }
        }

        $this->documentManager->flush();

        $this->executeNextJob();
    }

    private function createMediaWithJob(Job $job): MediaInterface
    {
        $multimediaObject = $this->jobValidator->ensureMultimediaObjectExists($job);

        return $this->mediaCreator->createMedia($multimediaObject, $job);
    }

    private function getNextJob()
    {
        return $this->documentManager->getRepository(Job::class)->findHigherPriorityWithStatus([Job::STATUS_WAITING]);
    }

    private function executeInBackground(Job $job): void
    {
        $multimediaObject = $this->jobValidator->ensureMultimediaObjectExists($job);
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

    private function checkExecutingJobs(): void
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
                $this->jobDispatcher->dispatch(EncoderEvents::JOB_ERROR, $job);
            }
        }

        if ($existsJobsToUpdate) {
            $this->documentManager->flush();
        }
    }

    private function getExecutor(?array $cpu): ExecutorInterface
    {
        return (in_array($cpu['host'], ['localhost', '127.0.0.1'])) ? new LocalExecutor() : new RemoteHTTPExecutor();
    }
}
