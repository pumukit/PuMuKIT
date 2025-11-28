<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\Controller;

use App\Series\Application\Create\CreateSeriesRequest;
use App\Series\Application\Create\CreateSeriesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class CreateSeriesController extends AbstractController
{
    public function __construct(private CreateSeriesService $createSeriesService) {}

    public function __invoke(): RedirectResponse
    {
        $user = $this->getUser();

        $request = new CreateSeriesRequest($user->getId());

        $response = ($this->createSeriesService)($request);

        $this->addFlash('success', sprintf(
            'Series "%s" created successfully',
            $response->series->getTitle()
        ));

        return $this->redirectToRoute('series_view', ['id' => $response->series->getId()]);
    }
}
