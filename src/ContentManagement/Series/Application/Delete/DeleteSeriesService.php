<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Delete;

use App\ContentManagement\MultimediaObject\Domain\Event\MultimediaObjectDeletedEvent;
use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\ContentManagement\Series\Domain\Event\SeriesDeletedEvent;
use App\ContentManagement\Series\Domain\Repository\SeriesRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final class DeleteSeriesService
{
    public function __construct(
        private readonly SeriesRepositoryInterface $repository,
        private readonly MultimediaObjectRepositoryInterface $multimediaRepository,
        private readonly EventBusInterface $eventBus
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

        $seriesTitle = $series->getTitle();

        $multimediaObjects = $this->multimediaRepository->findBySeriesId($series->getId());

        foreach ($multimediaObjects as $mo) {
            $this->multimediaRepository->delete($mo);
            $this->eventBus->dispatch(new MultimediaObjectDeletedEvent($mo));
        }

        $this->repository->delete($series);
        $this->eventBus->dispatch(new SeriesDeletedEvent($series));

        return new DeleteSeriesResponse(
            success: true,
            message: sprintf('Series "%s" deleted successfully.', $seriesTitle)
        );
    }
}
