<?php

namespace App\Series\Application\Delete;

use App\MultimediaObject\Domain\Event\MultimediaObjectDeletedEvent;
use App\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\Series\Domain\Event\SeriesDeletedEvent;
use App\Series\Domain\Repository\SeriesRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final class DeleteSeriesService
{
    public function __construct(
        private readonly SeriesRepositoryInterface $repository,
        private MultimediaObjectRepositoryInterface $multimediaRepository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(DeleteSeriesRequest $request): DeleteSeriesResponse
    {
        DeleteSeriesValidator::validate($request);

        $series = $this->repository->find($request->id);

        if (null === $series) {
            return new DeleteSeriesResponse(
                success: false,
                message: sprintf('Series with id "%s" not found.', $request->id)
            );
        }

        // Get series title before deletion for response message
        $seriesTitle = $series->getTitle();

        // Find and delete all multimedia objects in this series
        $multimediaObjects = $this->multimediaRepository->findBySeriesId($series->getId());

        foreach ($multimediaObjects as $mo) {
            $this->multimediaRepository->delete($mo);
            $this->eventBus->dispatch(new MultimediaObjectDeletedEvent($mo));
        }

        // Delete the series
        $this->repository->delete($series);
        $this->eventBus->dispatch(new SeriesDeletedEvent($series));

        return new DeleteSeriesResponse(
            success: true,
            message: sprintf('Series "%s" deleted successfully.', $seriesTitle)
        );
    }
}
