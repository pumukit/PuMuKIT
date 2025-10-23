<?php

namespace App\MultimediaObject\Application\ViewMultimediaObject;

use App\MultimediaObject\Domain\MultimediaObjectRepositoryInterface;

final class ViewMultimediaObjectHandler
{
    public function __construct(private MultimediaObjectRepositoryInterface $repository) {}

    public function handle(ViewMultimediaObjectQuery $query): ViewMultimediaObjectResponse
    {
        $object = $this->repository->find($query->id());
        if (!$object) {
            throw new \RuntimeException('MultimediaObject not found');
        }

        return new ViewMultimediaObjectResponse($object);
    }
}
