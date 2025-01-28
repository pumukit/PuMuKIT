<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\ProfileValidator;
use Pumukit\InspectionBundle\Services\InspectionDocumentService;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\InspectionBundle\Services\InspectionImageService;
use Pumukit\SchemaBundle\Document\MediaType\Document;
use Pumukit\SchemaBundle\Document\MediaType\External;
use Pumukit\SchemaBundle\Document\MediaType\Image;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\Exif;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\Generic;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\MediaMetadata;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;

final class MediaCreator implements MediaCreatorInterface
{
    private ProfileService $profileService;
    private ProfileValidator $profileValidator;
    private InspectionFfprobeService $inspectionService;
    private DocumentManager $documentManager;
    private TrackEventDispatcherService $dispatcher;
    private InspectionImageService $inspectionImageService;
    private InspectionDocumentService $inspectionDocumentService;

    public function __construct(
        DocumentManager $documentManager,
        ProfileService $profileService,
        TrackEventDispatcherService $dispatcher,
        ProfileValidator $profileValidator,
        InspectionFfprobeService $inspectionService,
        InspectionImageService $inspectionImageService,
        InspectionDocumentService $inspectionDocumentService,
    ) {
        $this->profileService = $profileService;
        $this->profileValidator = $profileValidator;
        $this->inspectionService = $inspectionService;
        $this->documentManager = $documentManager;
        $this->dispatcher = $dispatcher;
        $this->inspectionImageService = $inspectionImageService;
        $this->inspectionDocumentService = $inspectionDocumentService;
    }

    public function createMedia(MultimediaObject $multimediaObject, Job $job): MediaInterface
    {
        $profile = $this->profileValidator->ensureProfileExists($job->getProfile());
        $originalName = ($job->getPathIni() && $profile['master']) ? pathinfo($job->getPathIni(), PATHINFO_BASENAME) : '';
        $i18nDescription = !empty($job->getI18nDescription()) ? i18nText::create($job->getI18nDescription()) : i18nText::create([]);

        $mediaTags = $this->generateProfileTags($job, $profile);
        $isDownloadable = $profile['downloadable'] ?? false;

        $url = $this->generateMediaUrl($job, $profile);
        $path = Path::create($job->getPathEnd());
        $storage = Storage::create($url, $path);

        $media = $this->generateMedia($multimediaObject, $path, $originalName, $i18nDescription, $job->getLanguageId(), $mediaTags, $isDownloadable, $storage);

        $this->addMediaToMultimediaObject($multimediaObject, $media);

        return $media;
    }

    public function createMediaFromExternalURL(MultimediaObject $multimediaObject, string $externalUrl): MediaInterface
    {
        $multimediaObject->setExternalType();
        $this->documentManager->flush();

        $originalName = '';
        $i18nDescription = i18nText::create([]);
        $mediaTags = Tags::create(['display']);

        $url = StorageUrl::create($externalUrl);
        $path = Path::create('');
        $storage = Storage::external($url);
        $language = '';

        $media = $this->generateMedia($multimediaObject, $path, $originalName, $i18nDescription, $language, $mediaTags, false, $storage);

        $this->addMediaToMultimediaObject($multimediaObject, $media);

        return $media;
    }

    public function generateProfileTags(Job $job, array $profile): Tags
    {
        $tags = [];
        $tags[] = $this->profileService->generateProfileTag($job->getProfile());

        if ($profile['master']) {
            $tags[] = 'master';
        }
        if ($profile['display']) {
            $tags[] = 'display';
        }
        if (!empty($profile['tags'])) {
            foreach (array_filter(preg_split('/[,\s]+/', $profile['tags'])) as $tag) {
                $tags[] = trim($tag);
            }
        }

        return Tags::create($tags);
    }

    private function createImageMediaMetadata(Path $path): MediaMetadata
    {
        return Exif::create($this->inspectionImageService->getFileMetadataAsString($path));
    }

    private function createDocumentMediaMetadata(Path $path): MediaMetadata
    {
        return Exif::create($this->inspectionDocumentService->getFileMetadataAsString($path));
    }

    private function createTrackMediaMetadata(Path $path): MediaMetadata
    {
        return VideoAudio::create($this->inspectionService->getFileMetadataAsString($path));
    }

    private function createExternalMediaMetadata(): MediaMetadata
    {
        return Generic::create('');
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

        if ($media instanceof External) {
            $multimediaObject->addExternal($media);
        }

        if ($executeFlush) {
            $this->documentManager->flush();
        }

        $this->dispatcher->dispatchCreate($multimediaObject, $media);
    }

    private function generateMediaUrl(Job $job, array $profile): StorageUrl
    {
        $url = isset($profile['streamserver']['url_out']) ? str_replace(
            realpath($profile['streamserver']['dir_out']),
            $profile['streamserver']['url_out'],
            $job->getPathEnd()
        ) : '';

        return StorageUrl::create($url);
    }

    private function generateMedia(
        MultimediaObject $multimediaObject,
        Path $path,
        string $originalName,
        i18nText $i18nDescription,
        string $language,
        Tags $mediaTags,
        bool $isDownloadable,
        Storage $storage
    ): MediaInterface {
        if (MultimediaObject::TYPE_VIDEO === $multimediaObject->getType() || MultimediaObject::TYPE_AUDIO === $multimediaObject->getType()) {
            $mediaMetadata = $this->createTrackMediaMetadata($path);
            $media = Track::create(
                $originalName,
                $i18nDescription,
                $language,
                $mediaTags,
                !$mediaTags->contains('display'),
                $isDownloadable,
                0,
                $storage,
                $mediaMetadata
            );
        }

        if (MultimediaObject::TYPE_IMAGE === $multimediaObject->getType()) {
            $mediaMetadata = $this->createImageMediaMetadata($path);
            $media = Image::create(
                $originalName,
                $i18nDescription,
                $language,
                $mediaTags,
                !$mediaTags->contains('display'),
                $isDownloadable,
                0,
                $storage,
                $mediaMetadata
            );
        }

        if (MultimediaObject::TYPE_DOCUMENT === $multimediaObject->getType()) {
            $mediaMetadata = $this->createDocumentMediaMetadata($path);
            $media = Document::create(
                $originalName,
                $i18nDescription,
                $language,
                $mediaTags,
                !$mediaTags->contains('display'),
                $isDownloadable,
                0,
                $storage,
                $mediaMetadata
            );
        }

        if (MultimediaObject::TYPE_EXTERNAL === $multimediaObject->getType()) {
            $mediaMetadata = $this->createExternalMediaMetadata();
            $media = External::create(
                $originalName,
                $i18nDescription,
                $language,
                $mediaTags,
                !$mediaTags->contains('display'),
                $isDownloadable,
                0,
                $storage,
                $mediaMetadata
            );
        }

        if (!isset($media)) {
            throw new \Exception('Media type not supported');
        }

        return $media;
    }
}
