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

    public function onSeriesSync(SeriesEvent $event, $eventName)
    {
        $series = $event->getSeries();

        //TTK-21470: Since having a series in an Opencast object is not required, but it is in PuMuKIT
        // we need THIS series to not be synced to Opencast. Ideally series would be OPTIONAL.
        if ('default' == $series->getProperty('opencast')) {
            return;
        }

        switch ($eventName) {
        case 'series.update':
            $this->seriesSyncService->updateSeries($series);

            break;
        case 'series.create':
            if ($series->getProperty('opencast')) {
                $this->seriesSyncService->updateSeries($series);
            } else {
                $this->seriesSyncService->createSeries($series);
            }

            break;
        case 'series.delete':
            $this->seriesSyncService->deleteSeries($series);

            break;
        }
        $this->logger->addDebug('Synced Series "'.$series->getId().'" on the Opencast Server.');
    }
}
