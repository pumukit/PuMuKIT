<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Utils\FileSystemUtils;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\DTO\JobOptions;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class JobCreator
{
    private DocumentManager $documentManager;
    private JobExecutor $jobExecutor;
    private JobValidator $jobValidator;
    private ProfileValidator $profileValidator;
    private MultimediaObjectPropertyJobService $propService;
    private TokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;
    private ?string $tmpPath;

    public function __construct(
        DocumentManager $documentManager,
        JobExecutor $jobExecutor,
        JobValidator $jobValidator,
        ProfileValidator $profileValidator,
        MultimediaObjectPropertyJobService $propService,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        string $tmpPath = null
    ) {
        $this->documentManager = $documentManager;
        $this->jobExecutor = $jobExecutor;
        $this->jobValidator = $jobValidator;
        $this->profileValidator = $profileValidator;
        $this->propService = $propService;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->tmpPath = $tmpPath;
    }

    public function fromUploadedFile(MultimediaObject $multimediaObject, UploadedFile $file, JobOptions $jobOptions): MultimediaObject
    {
        $this->jobValidator->validateFile($file->getPathname());
        $fileName = $this->cleanFileName($file);

        $newFile = $file->move(
            $this->tmpPath.'/'.$multimediaObject->getId(),
            $fileName.'.'.pathinfo($file->getClientOriginalName())['extension']
        );

        $this->create($newFile->getPathname(), $multimediaObject, $jobOptions);

        return $multimediaObject;
    }

    public function fromPath(MultimediaObject $multimediaObject, Path $filePath, JobOptions $jobOptions): MultimediaObject
    {
        $this->jobValidator->validateFile($filePath->path());
        $this->create($filePath->path(), $multimediaObject, $jobOptions);

        return $multimediaObject;
    }

    private function create(string $pathFile, MultimediaObject $multimediaObject, JobOptions $jobOptions): void
    {
        if (!$this->jobValidator->isUniqueJob($multimediaObject, $jobOptions)) {
            return;
        }

        $job = $this->createByMimeType($multimediaObject, $jobOptions, $pathFile);
        $this->propService->addJob($multimediaObject, $job);
        $this->jobExecutor->executeNextJob();
    }

    private function cleanFileName(UploadedFile $file): string
    {
        $trackName = TextIndexUtils::cleanTextIndex(pathinfo($file->getClientOriginalName())['filename']);

        return preg_replace('([^A-Za-z0-9])', '', $trackName);
    }

    private function createByMimeType(MultimediaObject $multimediaObject, JobOptions $jobOptions, string $pathFile): Job
    {
        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($pathFile);

        $profile = $this->profileValidator->ensureProfileExists($jobOptions->profile());

        if (str_contains($mimeType, 'image/')) {
            $multimediaObject->setImageType();
            $this->documentManager->flush();

            return $this->generateJob($multimediaObject, $jobOptions, $pathFile, 0);
        }

        if (str_contains($mimeType, 'application/')) {;
            $multimediaObject->setDocumentType();
            $this->documentManager->flush();

            return $this->generateJob($multimediaObject, $jobOptions, $pathFile, 0);
        }

        if (str_contains($mimeType, 'video/')) {
            $multimediaObject->setVideoType();
            $this->documentManager->flush();

            $duration = $this->jobValidator->validateTrack($profile, $jobOptions, $pathFile);

            return $this->generateJob($multimediaObject, $jobOptions, $pathFile, $duration);
        }

        return $this->generateJob($multimediaObject, $jobOptions, $pathFile, 0);
    }

    private function generateJob(MultimediaObject $multimediaObject, JobOptions $jobOptions, string $pathFile, int $duration): Job
    {
        $job = new Job();
        $job->setMmId($multimediaObject->getId());
        $job->setProfile($jobOptions->profile());
        $job->setPathIni($pathFile);
        $job->setDuration($duration);
        $job->setPriority($jobOptions->priority());
        $job->setInitVars($jobOptions->initVars());
        if (null !== $jobOptions->language()) {
            $job->setLanguageId($jobOptions->language());
        }
        if (!empty($jobOptions->description())) {
            $job->setI18nDescription($jobOptions->description());
        }
        if ($email = $this->getUserEmail($job)) {
            $job->setEmail($email);
        }
        $job->setTimeini(new \DateTime('now'));
        $this->documentManager->persist($job);

        $this->setPathEndAndExtensions($job);

        $this->documentManager->flush();

        return $job;
    }

    private function getUserEmail(Job $job = null): ?string
    {
        if (null !== $token = $this->tokenStorage->getToken()) {
            if (($user = $token->getUser()) instanceof User) {
                return $user->getEmail();
            }
        }

        if ($job) {
            $otherJob = $this->documentManager->getRepository(Job::class)->findOneBy(
                ['mm_id' => $job->getMmId(), 'email' => ['$exists' => true]],
                ['timeini' => 1]
            );
            if ($otherJob && $otherJob->getEmail()) {
                return $otherJob->getEmail();
            }
        }

        return null;
    }

    private function setPathEndAndExtensions(Job $job): void
    {
        if (!FileSystemUtils::exists($job->getPathIni())) {
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

        $profile = $this->profileValidator->ensureProfileExists($job->getProfile());
        $multimediaObject = $this->jobValidator->ensureMultimediaObjectExists($job);

        $extension = pathinfo($job->getPathIni(), PATHINFO_EXTENSION);
        $pathEnd = $this->getPathEnd($profile, $multimediaObject->getSeries()->getId(), $job->getId(), $extension);

        $job->setPathEnd($pathEnd);
        $job->setExtIni($extension);
        $job->setExtEnd(pathinfo($pathEnd, PATHINFO_EXTENSION));
    }

    private function getPathEnd(array $profile, string $dir, string $file, string $extension): string
    {
        $finalExtension = $profile['extension'] ?? $extension;

        $tempDir = $profile['streamserver']['dir_out'].'/'.$dir;
        FileSystemUtils::createFolder($tempDir);

        return realpath($tempDir).'/'.$file.'.'.$finalExtension;
    }
}
