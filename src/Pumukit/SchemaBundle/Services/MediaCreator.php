<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\ProfileValidator;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\SchemaBundle\Document\MediaType\Document;
use Pumukit\SchemaBundle\Document\MediaType\Image;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Document\ValueObject\Url;

final class MediaCreator implements MediaCreatorInterface
{
    private ProfileService $profileService;
    private LoggerInterface $logger;
    private ProfileValidator $profileValidator;
    private InspectionFfprobeService $inspectionService;
    private DocumentManager $documentManager;
    private TrackEventDispatcherService $dispatcher;

    public function __construct(
        DocumentManager $documentManager,
        LoggerInterface $logger,
        ProfileService $profileService,
        TrackEventDispatcherService $dispatcher,
        ProfileValidator $profileValidator,
        InspectionFfprobeService $inspectionService
    ) {
        $this->profileService = $profileService;
        $this->logger = $logger;
        $this->profileValidator = $profileValidator;
        $this->inspectionService = $inspectionService;
        $this->documentManager = $documentManager;
        $this->dispatcher = $dispatcher;
    }

    public function createTrack(
        MultimediaObject $multimediaObject,
        Job $job
    ): MediaInterface {
        $profile = $this->profileValidator->ensureProfileExists($job->getProfile());
        $originalName = ($job->getPathIni() && $profile['master']) ? pathinfo($job->getPathIni(), PATHINFO_BASENAME) : '';
        $i18nDescription = !empty($job->getI18nDescription()) ? i18nText::create($job->getI18nDescription()) : i18nText::create([]);

        $tags[] = $this->profileService->generateProfileTag($job->getProfile());

        if ($profile['master']) {
            $tags[] = 'master';
        }
        if ($profile['display']) {
            $tags[] = 'display';
        }
        foreach (array_filter(preg_split('/[,\s]+/', $profile['tags'])) as $tag) {
            $tags[] = trim($tag);
        }

        $trackTags = Tags::create($tags);
        $isDownloadable = $profile['downloadable'] ?? false;

        $url = isset($profile['streamserver']['url_out']) ? str_replace(
            realpath($profile['streamserver']['dir_out']),
            $profile['streamserver']['url_out'],
            $job->getPathEnd()
        ) : '';

        $url = Url::create($url);
        $path = Path::create($job->getPathEnd());
        $storage = Storage::create($url, $path);

        $mediaMetadata = VideoAudio::create($this->inspectionService->getFileMetadataAsString($path));

        $track = Track::create(
            $originalName,
            $i18nDescription,
            $job->getLanguageId(),
            $trackTags,
            !$trackTags->contains('display'),
            $isDownloadable,
            0,
            $storage,
            $mediaMetadata
        );

        $multimediaObject->setDuration($track->metadata()->duration());
        $this->addMediaToMultimediaObject($multimediaObject, $track);

        return $track;
    }

    private function addMediaToMultimediaObject(MultimediaObject $multimediaObject, MediaInterface $media, bool $executeFlush = true): void
    {
        if ($media instanceof Track) {
            $multimediaObject->addTrack($media);
        }

        if ($media instanceof Image) {
            $multimediaObject->addImage($media);
        }

        if ($media instanceof Document) {
            $multimediaObject->addDocument($media);
        }

        if ($executeFlush) {
            $this->documentManager->flush();
        }

        $this->dispatcher->dispatchCreate($multimediaObject, $media);
    }
}
