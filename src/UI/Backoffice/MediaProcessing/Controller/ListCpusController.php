<?php

declare(strict_types=1);

namespace App\UI\Backoffice\MediaProcessing\Controller;

use App\MediaProcessing\Application\Cpu\List\ListCpusRequest;
use App\MediaProcessing\Application\Cpu\List\ListCpusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ListCpusController extends AbstractController
{
    public function __construct(private readonly ListCpusService $service) {}

    public function __invoke(): Response
    {
        $request = new ListCpusRequest();
        $response = ($this->service)($request);

        return $this->render('@MediaProcessing/Views//cpus.html.twig', [
            'cpusInMaintenance' => $response->cpusInMaintenance,
            'remoteCpus' => $response->remoteCpus,
            'localCpus' => $response->localCpus,
        ]);
    }
}
