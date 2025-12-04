<?php

declare(strict_types=1);

namespace App\Transcoding\UI\Backoffice\Controller;

use App\Transcoding\Application\Job\Cancel\CancelJobRequest;
use App\Transcoding\Application\Job\Cancel\CancelJobService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CancelJobController extends AbstractController
{
    public function __construct(private readonly CancelJobService $service) {}

    public function __invoke(string $id): Response
    {
        $request = new CancelJobRequest($id);
        $response = ($this->service)($request);

        return new JsonResponse([
            'success' => true,
            'job' => [
                'id' => $response->job->getId(),
                'status' => $response->job->getStatus(),
            ],
        ]);
    }
}
