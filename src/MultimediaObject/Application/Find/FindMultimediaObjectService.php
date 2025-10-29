<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\Find;

use App\MultimediaObject\Domain\Exception\MultimediaObjectNotFoundException;
use App\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;

final class FindMultimediaObjectService
{
    public function __construct(
        private MultimediaObjectRepositoryInterface $repository
    ) {}

    public function __invoke(FindMultimediaObjectRequest $request): FindMultimediaObjectResponse
    {
        FindMultimediaObjectValidator::validate($request);

        $multimediaObject = $this->repository->find($request->id);

        if (!$multimediaObject) {
            throw new MultimediaObjectNotFoundException($request->id);
        }

        return new FindMultimediaObjectResponse($multimediaObject);
    }
}
