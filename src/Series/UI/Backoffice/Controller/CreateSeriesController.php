<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\Controller;

use App\Series\Application\Create\CreateSeriesRequest;
use App\Series\Application\Create\CreateSeriesService;
use App\Shared\Domain\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class CreateSeriesController extends AbstractController
{
    public function __construct(
        private readonly CreateSeriesService $createSeriesService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(): RedirectResponse
    {
        try {
            $user = $this->getUser();

            $request = new CreateSeriesRequest($user->getId());

            $response = ($this->createSeriesService)($request);

            $this->addFlash('success', sprintf(
                'Series "%s" created successfully',
                $response->series->getTitle()
            ));

            return $this->redirectToRoute('series_view', ['id' => $response->series->getId()]);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('series_list');
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error creating series', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', 'An error occurred while creating the series');

            return $this->redirectToRoute('series_list');
        }
    }
}
