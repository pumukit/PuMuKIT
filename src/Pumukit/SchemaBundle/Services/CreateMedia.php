<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Url;
use Pumukit\SchemaBundle\Services\CreateMediaInterface;

abstract class CreateMedia implements CreateMediaInterface
{
    private $documentManager;
    private $profileService;

    public function __construct(DocumentManager $documentManager, ProfileService $profileService)
    {
        $this->documentManager = $documentManager;
        $this->profileService = $profileService;
    }

    abstract public static function create(MultimediaObject $multimediaObject, Job $job);

    protected function createLocalStorage(Job $job): Storage
    {
        return Storage::local($this->generateStorageUrl($job), $this->generateStoragePath($job));
    }

    protected function createS3Storage(Job $job): Storage
    {
        return Storage::s3($this->generateStorageUrl($job), $this->generateStoragePath($job));
    }

    protected function createExternalStorage(Job $job): Storage
    {
        return Storage::external($this->generateStorageUrl($job));
    }

    protected function generateStorageUrl(Job $job): Url
    {
        return Url::create($this->generateUrlFromProfile($job));
    }

    protected function generateStoragePath(Job $job): Path
    {
        return Path::create($job->getPathEnd());
    }

    protected function profileFromJob(Job $job): ?array
    {
        return $this->profileService->getProfile($job->getProfile());
    }

    protected function generateUrlFromProfile(Job $job): ?string
    {
        $profile = $this->profileFromJob($job);

        if (!isset($profile['streamserver']['url_out'])) {
            return null;
        }

        return str_replace(
            realpath($profile['streamserver']['dir_out']),$profile['streamserver']['url_out'], $job->getPathEnd()
        );
    }

    protected function originalName(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }
}
