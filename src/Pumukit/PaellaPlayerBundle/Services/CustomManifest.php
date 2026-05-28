<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Services;

use Pumukit\PaellaPlayerBundle\PumukitPaellaPlayerBundle;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\HeadAndTailService;

class CustomManifest
{
    private HeadAndTailService $headAndTailService;

    public function __construct(HeadAndTailService $headAndTailService)
    {
        $this->headAndTailService = $headAndTailService;
    }

    public function completeManifestData(MultimediaObject $multimediaObject, array $data): array
    {
        if (!$this->checkManifestData($data)) {
            throw new \Exception('Malformed manifest to add custom properties');
        }

        $data = $this->addIntroManifestURL($multimediaObject, $data);

        return $this->addTailManifestURL($multimediaObject, $data);
    }

    private function checkManifestData(array $data): bool
    {
        return isset($data['metadata']);
    }

    private function addIntroManifestURL(MultimediaObject $multimediaObject, array $data): array
    {
        $head = $this->headAndTailService->getHeadToPlay($multimediaObject);
        if (!$head) {
            return $data;
        }

        $data['intro'] = $this->generatePaellaRepositoryURL($head);

        return $data;
    }

    private function addTailManifestURL(MultimediaObject $multimediaObject, array $data): array
    {
        $tail = $this->headAndTailService->getTailToPlay($multimediaObject);
        if (!$tail) {
            return $data;
        }

        $data['tail'] = $this->generatePaellaRepositoryURL($tail);

        return $data;
    }

    private function generatePaellaRepositoryURL(string $videoID): string
    {
        return '/'.PumukitPaellaPlayerBundle::paellaRepositoryPath().'/'.$videoID;
    }
}
