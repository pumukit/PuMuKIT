<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\DTO\JobOptions;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

final class jobCreator
{
    private DocumentManager $documentManager;
    private JobExecutor $jobExecutor;
    private MultimediaObjectPropertyJobService $propService;
    private ProfileService $profileService;
    private LoggerInterface $logger;
    private ?string $tmpPath;

    public function __construct(
        DocumentManager $documentManager,
        JobExecutor $jobExecutor,
        MultimediaObjectPropertyJobService $propService,
        ProfileService $profileService,
        LoggerInterface $logger,
        string $tmpPath = null
    )
    {
        $this->documentManager = $documentManager;
        $this->jobExecutor = $jobExecutor;
        $this->propService = $propService;
        $this->profileService = $profileService;
        $this->logger = $logger;
        $this->tmpPath = $tmpPath;
    }

    public function fromUploadedFile(MultimediaObject $multimediaObject, UploadedFile $file, JobOptions $jobOptions): MultimediaObject
    {
        $this->validateFile($file);
        $fileName = $this->cleanFileName($file);

        $newFile = $file->move(
            $this->tmpPath.'/'.$multimediaObject->getId(),
            $fileName.'.'.pathinfo($file->getClientOriginalName())['extension']
        );

        $this->create($newFile->getPathname(), $multimediaObject, $jobOptions);

        return $multimediaObject;
    }

    public function fromPath(MultimediaObject $multimediaObject, string $filePath, JobOptions $jobOptions): MultimediaObject
    {
        $this->validateFile($filePath);
        $this->create($filePath, $multimediaObject, $jobOptions);
        return $multimediaObject;
    }

    private function create(string $pathFile, MultimediaObject $multimediaObject, JobOptions $jobOptions): void
    {
        if($jobOptions->unique() && !empty($jobOptions->flags())) {
            $job = $this->documentManager->getRepository(Job::class)->findOneBy([
                    'profile' => $jobOptions->profile(),
                    'mm_id' => $multimediaObject->getId()]
            );

            if ($job) {
                return;
            }
        }

        $job = $this->createByMimeType($multimediaObject, $jobOptions, $pathFile);
        $this->propService->addJob($multimediaObject, $job);

        $this->jobExecutor->executeNextJob();
    }


    private function validateFile($file): void
    {
        if($file instanceof UploadedFile) {
            if (!$file->isValid()) {
                throw new \Exception($file->getErrorMessage());
            }

            if (!is_file($file->getPathname())) {
                throw new FileNotFoundException($file->getPathname());
            }
        }

        if (!is_file($file)) {
            throw new FileNotFoundException($file);
        }
    }

    private function cleanFileName(UploadedFile $file): string
    {
        $trackName = TextIndexUtils::cleanTextIndex(pathinfo($file->getClientOriginalName())['filename']);

        return preg_replace('([^A-Za-z0-9])', '', $trackName);
    }

    private function validateProfileName($profileName): array
    {
        if (null === $profile = $this->profileService->getProfile($profileName)) {
            $this->logger->error('[addJob] Can not find given profile with name "' . $profileName);

            throw new \Exception("Can't find given profile with name " . $profileName);
        }

        return $profile;
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


    private function createByMimeType(MultimediaObject $multimediaObject, JobOptions $jobOptions, $pathFile): Job
    {
        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($pathFile);

        $profile = $this->validateProfileName($jobOptions->profile());

        if(str_contains($mimeType, 'image/')) {
            return $this->jobCreator($multimediaObject, $jobOptions, $pathFile, 0);
        }

        if(str_contains($mimeType, 'video/')) {
            $duration = $this->validateTrack($profile, $jobOptions, $pathFile);
            return $this->jobCreator($multimediaObject, $jobOptions, $pathFile, $duration);
        }

        return $this->jobCreator($multimediaObject, $jobOptions, $pathFile, 0);
    }

    private function jobCreator(MultimediaObject $multimediaObject, JobOptions $jobOptions, $pathFile, int $duration): Job
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
        $this->documentManager->flush();

        $this->setPathEndAndExtensions($job);

        return $job;
    }

    private function setPathEndAndExtensions(Job $job): void
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

        $this->documentManager->flush();
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

    private function getPathEnd(array $profile, $dir, $file, $extension): string
    {
        $finalExtension = $profile['extension'] ?? $extension;

        $tempDir = $profile['streamserver']['dir_out'].'/'.$dir;

        $this->mkdir($tempDir);

        return realpath($tempDir).'/'.$file.'.'.$finalExtension;
    }

    private function mkdir(string $path): void
    {
        $fs = new Filesystem();
        $fs->mkdir($path);
    }

    private function validateTrack(array $profile, JobOptions $jobOptions, string $pathFile): int
    {
        $checkduration = !(isset($profile['nocheckduration']) && $profile['nocheckduration']);

        if ($checkduration && !($jobOptions->unique() && $jobOptions->flags())) {
            if (!is_file($pathFile)) {
                $this->logger->error('[addJob] FileNotFoundException: Could not find file "' . $pathFile);

                throw new FileNotFoundException($pathFile);
            }
            $this->logger->info('Not doing duration checks on job with profile' . $jobOptions->profile());

            try {
                $duration = $this->inspectionService->getDuration($pathFile);
            } catch (\Exception $e) {
                $this->logger->error('[addJob] InspectionService getDuration error message: ' . $e->getMessage());

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
        return $duration;
    }
}
