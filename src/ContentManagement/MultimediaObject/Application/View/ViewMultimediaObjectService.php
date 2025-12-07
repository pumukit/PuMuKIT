<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\View;

use App\ContentManagement\MultimediaObject\Application\Find\FindMultimediaObjectRequest;
use App\ContentManagement\MultimediaObject\Application\Find\FindMultimediaObjectService;

final class ViewMultimediaObjectService
{
    public function __construct(
        private FindMultimediaObjectService $findMultimediaObjectService
    ) {}

    public function __invoke(ViewMultimediaObjectRequest $request): ViewMultimediaObjectResponse
    {
        ViewMultimediaObjectValidator::validate($request);

        $findRequest = new FindMultimediaObjectRequest($request->id);
        $findResponse = ($this->findMultimediaObjectService)($findRequest);

        return new ViewMultimediaObjectResponse(
            $findResponse->multimediaObject,
            $request->tab
        );
    }
}
