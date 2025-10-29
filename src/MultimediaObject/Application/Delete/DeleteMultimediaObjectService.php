<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\Delete;

use App\MultimediaObject\Domain\Event\MultimediaObjectDeletedEvent;
use App\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeleteMultimediaObjectService
{
    public function __construct(
        private readonly MultimediaObjectRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(DeleteMultimediaObjectRequest $request): DeleteMultimediaObjectResponse
    {
        DeleteMultimediaObjectValidator::validate($request);

        $multimediaObject = $this->repository->find($request->id);

        if (null === $multimediaObject) {
            return new DeleteMultimediaObjectResponse(
                success: false,
                message: sprintf('MultimediaObject with id "%s" not found.', $request->id),
                series: null
            );
        }

        $title = $multimediaObject->getTitle();
        $series = $multimediaObject->getSeries()->getId();

        $this->repository->delete($multimediaObject);
        $this->eventDispatcher->dispatch(
            new MultimediaObjectDeletedEvent($multimediaObject),
            MultimediaObjectDeletedEvent::NAME
        );

        return new DeleteMultimediaObjectResponse(
            success: true,
            message: sprintf('MultimediaObject "%s" deleted successfully.', $title),
            series: $series
        );
    }
}

