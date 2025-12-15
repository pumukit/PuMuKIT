<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\Find;

use App\ContentManagement\MultimediaObject\Domain\Exception\MultimediaObjectNotFoundException;
use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;

final class FindMultimediaObjectService
{
    public function __construct(
        private MultimediaObjectRepositoryInterface $repository
    ) {}

    public function __invoke(FindMultimediaObjectRequest $request): FindMultimediaObjectResponse
    {
        FindMultimediaObjectValidator::validate($request);

        $multimediaObject = $this->repository->find($request->id);

        if (!$multimediaObject instanceof MultimediaObject) {
            throw new MultimediaObjectNotFoundException($request->id);
        }

        return new FindMultimediaObjectResponse($multimediaObject);
    }
}
