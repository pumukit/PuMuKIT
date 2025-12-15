<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Infrastructure\Ui\Backoffice\Http\Controller;

use App\MediaProcessing\Job\Application\Find\FindJobRequest;
use App\MediaProcessing\Job\Application\Find\FindJobService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ViewJobController extends AbstractController
{
    public function __construct(private readonly FindJobService $service) {}

    public function __invoke(string $id): Response
    {
        $request = new FindJobRequest($id);
        $response = ($this->service)($request);

        return $this->render('@Job/Views/view.html.twig', [
            'job' => $response->job,
        ]);
    }
}
