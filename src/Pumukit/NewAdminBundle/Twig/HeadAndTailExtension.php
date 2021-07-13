<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Twig;

use Pumukit\SchemaBundle\Services\HeadAndTailService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HeadAndTailExtension extends AbstractExtension
{
    private $headAndTailService;

    public function __construct(HeadAndTailService $headAndTailService)
    {
        $this->headAndTailService = $headAndTailService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('default_video_head', [$this, 'getSystemDefaultHeader']),
            new TwigFunction('default_video_tail', [$this, 'getSystemDefaultTail']),
            new TwigFunction('videos_as_head', [$this, 'getVideosAsHead']),
            new TwigFunction('videos_as_tail', [$this, 'getVideosAsTail']),
        ];
    }

    public function getSystemDefaultHeader(): ?string
    {
        return $this->headAndTailService->getSystemDefaultHeader();
    }

    public function getSystemDefaultTail(): ?string
    {
        return $this->headAndTailService->getSystemDefaultTail();
    }

    public function getVideosAsHead(): array
    {
        return $this->headAndTailService->getVideosAsHead();
    }

    public function getVideosAsTail(): array
    {
        return $this->headAndTailService->getVideosAsTail();
    }
}
