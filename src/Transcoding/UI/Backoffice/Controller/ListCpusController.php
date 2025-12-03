<?php
declare(strict_types=1);

namespace App\Transcoding\UI\Backoffice\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Transcoding\Application\Cpu\List\ListCpusService;
use App\Transcoding\Application\Cpu\List\ListCpusRequest;


final class ListCpusController extends AbstractController
{
    public function __construct(private readonly ListCpusService $service)
    {
    }

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




