<?php

declare(strict_types=1);

namespace App\Series\Application\Update;

use App\Series\Domain\Event\SeriesUpdatedEvent;
use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Series\Domain\Repository\SeriesRepositoryInterface;
use App\Series\Domain\Repository\SeriesStyleRepositoryInterface;
use App\Series\Domain\Repository\SeriesTypeRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final class UpdateSeriesService
{
    public function __construct(
        private SeriesRepositoryInterface $seriesRepository,
        private SeriesTypeRepositoryInterface $seriesTypeRepository,
        private SeriesStyleRepositoryInterface $seriesStyleRepository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(UpdateSeriesRequest $request): UpdateSeriesResponse
    {
        UpdateSeriesValidator::validate($request);

        $series = $this->seriesRepository->find($request->id);

        if (!$series) {
            throw new SeriesNotFoundException($request->id);
        }

        if (null !== $request->title) {
            $series->setI18nTitle($request->title);
        }

        if (null !== $request->subtitle) {
            $series->setI18nSubtitle($request->subtitle);
        }

        if (null !== $request->description) {
            $series->setI18nDescription($request->description);
        }

        if (null !== $request->header) {
            $series->setI18nHeader($request->header);
        }

        if (null !== $request->footer) {
            $series->setI18nFooter($request->footer);
        }

        if (null !== $request->comments) {
            $series->setComments($request->comments);
        }

        if (null !== $request->keywords) {
            $series->setI18nKeywords($request->keywords);
        }

        if (null !== $request->announce) {
            $series->setAnnounce($request->announce);
        }

        if (null !== $request->hide) {
            $series->setHide($request->hide);
        }

        if (null !== $request->publicDate) {
            $series->setPublicDate($request->publicDate);
        }

        if (null !== $request->sorting) {
            $series->setSorting($request->sorting);
        }

        if (null !== $request->seriesTypeId) {
            $seriesType = $this->seriesTypeRepository->find($request->seriesTypeId);
            if (!$seriesType) {
                throw new \InvalidArgumentException(
                    sprintf('Series Type with ID %s not found', $request->seriesTypeId)
                );
            }
            $series->setSeriesType($seriesType);
        }

        if (null !== $request->seriesStyleId) {
            $seriesStyle = $this->seriesStyleRepository->find($request->seriesStyleId);
            if (!$seriesStyle) {
                throw new \InvalidArgumentException(
                    sprintf('Series Style with ID %s not found', $request->seriesStyleId)
                );
            }
            $series->setSeriesStyle($seriesStyle);
        }

        if (null !== $request->properties) {
            foreach ($request->properties as $key => $value) {
                if (null === $value) {
                    $series->removeProperty($key);
                } else {
                    $series->setProperty($key, $value);
                }
            }
        }

        $this->seriesRepository->save($series);

        $this->eventBus->dispatch(new SeriesUpdatedEvent($series));

        return new UpdateSeriesResponse($series);
    }
}
