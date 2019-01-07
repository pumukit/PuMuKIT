<?php

namespace Pumukit\OpencastBundle\EventListener;

use Psr\Log\LoggerInterface;
use Pumukit\OpencastBundle\Services\SeriesSyncService;
use Pumukit\SchemaBundle\Event\SeriesEvent;

class SeriesListener
{
    private $seriesSyncService;
    private $logger;

    public function __construct(SeriesSyncService $seriesSyncService, LoggerInterface $logger)
    {
        $this->seriesSyncService = $seriesSyncService;
        $this->logger = $logger;
    }

    public function onSeriesSync(SeriesEvent $event)
    {
        $series = $event->getSeries();
        switch ($event->getName()) {
        case 'series.update':
            $this->seriesSyncService->updateSeries($series);
            break;
        case 'series.create':
            $this->seriesSyncService->createSeries($series);
            break;
        case 'series.delete':
            $this->seriesSyncService->deleteSeries($series);
            break;
        }
        $this->logger->addDebug('Synced Series "'.$series->getId().'" on the Opencast Server.');
    }
}
