<?php

declare(strict_types=1);

namespace App\Series\UI\Backend\Twig;

use App\Series\Application\ViewSeriesOwners\GetSeriesOwnersRequest;
use App\Series\Application\ViewSeriesOwners\GetSeriesOwnersService;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\PicService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SeriesExtension extends AbstractExtension
{
    public function __construct(
        private PicService $picService,
        private GetSeriesOwnersService $getSeriesOwnersService
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('image_filter', $this->imageFilter(...)),
            new TwigFilter('owner_filter', $this->ownerFilter(...)),
        ];
    }

    public function imageFilter(Series $series): string
    {
        return $this->picService->getFirstUrlPic($series);
    }

    public function ownerFilter(Series $series): array
    {
        $request = new GetSeriesOwnersRequest($series->getId());
        $response = ($this->getSeriesOwnersService)($request);

        return $response->owners;
    }
}


