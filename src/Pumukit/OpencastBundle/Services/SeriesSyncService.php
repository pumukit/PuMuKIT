<?php

namespace Pumukit\OpencastBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class SeriesSyncService
{
    private $dm;
    private $clientService;
    private $logger;

    public function __construct(DocumentManager $dm, ClientService $clientService, LoggerInterface $logger)
    {
        $this->dm = $dm;
        $this->clientService = $clientService;
        $this->logger = $logger;
    }

    public function createSeries($series)
    {
        try {
            $output = $this->clientService->createOpencastSeries($series);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());

            return;
        }

        $seriesOpencastId = json_decode($output['var'], true)['identifier'];
        $series->setProperty('opencast', $seriesOpencastId);
        $this->dm->persist($series);
        $this->dm->flush();
    }

    public function updateSeries($series)
    {
        try {
            $output = $this->clientService->updateOpencastSeries($series);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
            if ($e->getCode() !== 404) {
                return;
            }
            $this->createSeries($series);
        }
    }

    public function deleteSeries($series)
    {
        try {
            $output = $this->clientService->deleteOpencastSeries($series);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());

            return;
        }
    }
}
