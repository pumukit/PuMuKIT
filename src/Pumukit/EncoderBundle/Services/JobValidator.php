<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Exception\FileNotValid;
use Pumukit\EncoderBundle\Services\DTO\JobOptions;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class JobValidator
{
    private DocumentManager $documentManager;
    private ProfileService $profileService;
    private InspectionFfprobeService $inspectionService;
    private LoggerInterface $logger;

    public function __construct(DocumentManager $documentManager, ProfileService $profileService, InspectionFfprobeService $inspectionService, LoggerInterface $logger)
    {
        $this->documentManager = $documentManager;
        $this->profileService = $profileService;
        $this->inspectionService = $inspectionService;
        $this->logger = $logger;
    }

    public function validateFile($file): void
    {
        if ($file instanceof UploadedFile) {
            if (!$file->isValid()) {
                throw new FileNotValid($file->getErrorMessage());
            }

            if (!is_file($file->getPathname())) {
                throw new FileNotFoundException($file->getPathname());
            }
        }

        if (!is_file($file)) {
            throw new FileNotFoundException($file);
        }
    }

    public function isUniqueJob(MultimediaObject $multimediaObject, JobOptions $jobOptions): bool
    {
        if ($jobOptions->unique() && !empty($jobOptions->flags())) {
            $job = $this->documentManager->getRepository(Job::class)->findOneBy([
                'profile' => $jobOptions->profile(),
                'mm_id' => $multimediaObject->getId(),
            ]);

            if ($job) {
                return false;
            }
        }

        return true;
    }

    public function ensureMultimediaObjectExists(Job $job): MultimediaObject
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

    public function validateTrack(array $profile, JobOptions $jobOptions, string $pathFile): int
    {
        $checkduration = !(isset($profile['nocheckduration']) && $profile['nocheckduration']);

        if ($checkduration && !($jobOptions->unique() && $jobOptions->flags())) {
            if (!is_file($pathFile)) {
                $this->logger->error('[addJob] FileNotFoundException: Could not find file "'.$pathFile);

                throw new FileNotFoundException($pathFile);
            }
            $this->logger->info('Not doing duration checks on job with profile'.$jobOptions->profile());

            try {
                $duration = $this->inspectionService->getDuration($pathFile);
            } catch (\Exception $e) {
                $this->logger->error('[addJob] InspectionService getDuration error message: '.$e->getMessage());

                throw new \Exception($e->getMessage());
            }

            if (0 === $duration) {
                $this->logger->error('[addJob] File duration is zero');

                throw new \Exception('File duration is zero');
            }
        }

        if ($checkduration && 0 === $duration) {
            throw new \Exception('The media file duration is zero');
        }

        return $duration;
    }

    public function searchError(array $profile, int $durationIn, int $durationEnd): void
    {
        if (isset($profile['nocheckduration']) && $profile['nocheckduration']) {
            return;
        }

        $duration_conf = 25;
        if (($durationIn < $durationEnd - $duration_conf) || ($durationIn > $durationEnd + $duration_conf)) {
            throw new \Exception(
                sprintf('Final duration (%s) and initial duration (%s) are different', $durationEnd, $durationIn)
            );
        }
    }
}
