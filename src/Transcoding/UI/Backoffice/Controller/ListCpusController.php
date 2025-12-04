<?php

declare(strict_types=1);

namespace App\Transcoding\UI\Backoffice\Controller;

use App\Transcoding\Application\Cpu\List\ListCpusRequest;
use App\Transcoding\Application\Cpu\List\ListCpusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ListCpusController extends AbstractController
{
    public function __construct(private readonly ListCpusService $service) {}

    public function __invoke(): Response
    {
        $request = new ListCpusRequest();
        $response = ($this->service)($request);

        return $this->render('@Transcoding/UI/Backoffice/Views/cpus.html.twig', [
            'cpusInMaintenance' => $response->cpusInMaintenance,
            'remoteCpus' => $response->remoteCpus,
            'localCpus' => $response->localCpus,
        ]);
    }
}
