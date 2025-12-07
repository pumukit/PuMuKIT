<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Series\Twig;

use App\ContentManagement\Series\Application\ViewSeriesOwners\ViewSeriesOwnersRequest;
use App\ContentManagement\Series\Application\ViewSeriesOwners\ViewSeriesOwnersService;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\PicService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SeriesExtension extends AbstractExtension
{
    public function __construct(
        private PicService $picService,
        private ViewSeriesOwnersService $viewSeriesOwnersService
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
        $request = new ViewSeriesOwnersRequest($series->getId());
        $response = ($this->viewSeriesOwnersService)($request);

        return $response->owners;
    }
}
