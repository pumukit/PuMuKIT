<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\MaterialService;

class CaptionsManifest
{
    private $materialService;
    private $requestContextScheme;
    private $requestContextHost;

    public function __construct(
        MaterialService $materialService,
        string $requestContextScheme,
        string $requestContextHost
    ) {
        $this->materialService = $materialService;
        $this->requestContextScheme = $requestContextScheme;
        $this->requestContextHost = $requestContextHost;
    }

    public function create(MultimediaObject $multimediaObject)
    {
        return $this->getCaptions($multimediaObject);
    }

    private function getCaptions(MultimediaObject $multimediaObject): array
    {
        $captions = $this->materialService->getCaptions($multimediaObject);

        $captionsManifest = array_map(
            function ($material) {
                return [
                    'lang' => $material->getLanguage(),
                    'text' => $material->getName() ?: $material->getLanguage(),
                    'format' => $material->getMimeType(),
                    'url' => $this->getAbsoluteUrl($material->getUrl()),
                ];
            },
            $captions->toArray()
        );

        return array_values($captionsManifest);
    }

    private function getAbsoluteUrl(string $url): string
    {
        return $this->requestContextScheme.'://'.$this->requestContextHost.$url;
    }
}
