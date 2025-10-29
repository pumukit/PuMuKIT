<?php

namespace App\Series\Application\Delete;

use App\MultimediaObject\Domain\Event\MultimediaObjectDeletedEvent;
use App\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\Series\Domain\Event\SeriesDeletedEvent;
use App\Series\Domain\Repository\SeriesRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeleteSeriesService
{
    public function __construct(
        private readonly SeriesRepositoryInterface $repository,
        private MultimediaObjectRepositoryInterface $multimediaRepository,
        private EventDispatcherInterface $eventDispatcher
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

        $multimediaObjects = $this->multimediaRepository->findBySeriesId($series->getId());
        foreach ($multimediaObjects as $mo) {
            $this->multimediaRepository->delete($mo);
            $this->eventDispatcher->dispatch(new MultimediaObjectDeletedEvent($mo), MultimediaObjectDeletedEvent::NAME);
        }

        $this->repository->delete($series);
        $this->eventDispatcher->dispatch(new SeriesDeletedEvent($series), SeriesDeletedEvent::NAME);

        return new DeleteSeriesResponse(
            success: true,
            message: sprintf('Series "%s" deleted successfully.', $series->getTitle())
        );
    }
}
