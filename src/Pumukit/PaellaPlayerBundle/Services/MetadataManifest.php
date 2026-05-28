<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\PicService;

class MetadataManifest
{
    protected $picService;

    public function __construct(PicService $picService)
    {
        $this->picService = $picService;
    }

    public function create(MultimediaObject $multimediaObject): array
    {
        $data = $this->addMandatoryManifestMetadata($multimediaObject);
        $data = $this->addCustomManifestMetadata($multimediaObject, $data);

        return $this->addRelatedManifestMetadata($multimediaObject, $data);
    }

    public function addMandatoryManifestMetadata(MultimediaObject $multimediaObject): array
    {
        $data = [];
        $data['id'] = $multimediaObject->getId();
        $data['title'] = $multimediaObject->getTitle();
        $data['preview'] = $this->getPreview($multimediaObject);
        $data['duration'] = $multimediaObject->getDuration();

        return $data;
    }

    public function addCustomManifestMetadata(MultimediaObject $multimediaObject, array $data): array
    {
        $data['i18nTitle'] = $multimediaObject->getI18nTitle();
        $data['description'] = $multimediaObject->getDescription();
        $data['i18nDescription'] = $multimediaObject->getI18nDescription();

        return $data;
    }

    public function addRelatedManifestMetadata(MultimediaObject $multimediaObject, array $data): array
    {
        $createdRelatedElement = [
            //            "title" => "Video with independent audio",
            //            "url" => "index.html?id=dual-video-audio",
            //            "thumb" => "https://repository.paellaplayer.upv.es/belmar-multiresolution/preview/belmar-preview.jpg",
            //            "id" => "dual-video-audio"
        ];

        $data['related'][] = $createdRelatedElement;

        return $data;
    }

    private function getPreview(MultimediaObject $multimediaObject)
    {
        $image = $this->picService->getPosterUrl($multimediaObject, true);
        if (!$image) {
            $image = $this->picService->getFirstUrlPic($multimediaObject, true, true);
        }

        return $image;
    }
}
