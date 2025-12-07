<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\View;

use App\ContentManagement\Series\Application\Find\FindSeriesRequest;
use App\ContentManagement\Series\Application\Find\FindSeriesService;

final class ViewSeriesService
{
    public function __construct(private readonly FindSeriesService $findSeriesService) {}

    public function __invoke(ViewSeriesRequest $request): ViewSeriesResponse
    {
        $findRequest = new FindSeriesRequest($request->id);
        $findResponse = ($this->findSeriesService)($findRequest);

        return new ViewSeriesResponse($findResponse->series, [], []);
    }
}
