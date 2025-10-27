<?php

namespace App\Series\Application\View;

use App\Series\Application\Find\FindSeriesRequest;
use App\Series\Application\Find\FindSeriesService;

final class ViewSeriesService
{
    public function __construct(private FindSeriesService $findSeriesService) {}

    public function __invoke(ViewSeriesRequest $request): ViewSeriesResponse
    {
        ViewSeriesValidator::validate($request);

        $findRequest = new FindSeriesRequest($request->id);
        $findResponse = ($this->findSeriesService)($findRequest);

        return new ViewSeriesResponse($findResponse->series, [], []);
    }
}
