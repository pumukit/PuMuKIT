<?php

declare(strict_types=1);

namespace App\Transcoding\UI\Backoffice\Controller;

use App\Transcoding\Application\Job\Find\FindJobRequest;
use App\Transcoding\Application\Job\Find\FindJobService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ViewJobController extends AbstractController
{
    public function __construct(private readonly FindJobService $service) {}

    public function __invoke(string $id): Response
    {
        $request = new FindJobRequest($id);
        $response = ($this->service)($request);

        return $this->render('@Transcoding/UI/Backoffice/Views/view.html.twig', [
            'job' => $response->job,
        ]);
    }
}
