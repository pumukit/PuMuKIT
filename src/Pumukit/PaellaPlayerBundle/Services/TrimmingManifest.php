<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Annotation;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class TrimmingManifest
{
    protected $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function create(MultimediaObject $multimediaObject): array
    {
        return $this->processTrimmingData($multimediaObject);
    }

    private function processTrimmingData(MultimediaObject $multimediaObject): array
    {
        $trimmingData = $this->documentManager->getRepository(Annotation::class)->findOneBy([
            'multimediaObject' => $multimediaObject->getId(),
            'type' => 'paella/trimming',
        ]);

        if (!$trimmingData) {
            return [];
        }

        $trimmingAnnotation = json_decode($trimmingData->getValue(), true, 512, JSON_THROW_ON_ERROR);

        return [
            'start' => $trimmingAnnotation['trimming']['start'],
            'end' => $trimmingAnnotation['trimming']['end'],
            'enabled' => $trimmingAnnotation['trimming']['start'] < $trimmingAnnotation['trimming']['end'],
        ];
    }
}
