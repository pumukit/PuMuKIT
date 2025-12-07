<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\Delete;

use App\ContentManagement\MultimediaObject\Domain\Event\MultimediaObjectDeletedEvent;
use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final class DeleteMultimediaObjectService
{
    public function __construct(
        private readonly MultimediaObjectRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus
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
        $this->eventBus->dispatch(new MultimediaObjectDeletedEvent($multimediaObject));

        return new DeleteMultimediaObjectResponse(
            success: true,
            message: sprintf('MultimediaObject "%s" deleted successfully.', $title),
            series: $series
        );
    }
}
