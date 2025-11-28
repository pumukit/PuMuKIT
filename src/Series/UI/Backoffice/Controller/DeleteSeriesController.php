<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\Controller;

use App\Series\Application\Delete\DeleteSeriesRequest;
use App\Series\Application\Delete\DeleteSeriesService;
use App\Shared\Domain\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class DeleteSeriesController extends AbstractController
{
    public function __construct(
        private readonly DeleteSeriesService $deleteSeriesService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(string $id): RedirectResponse
    {
        try {
            $requestDto = new DeleteSeriesRequest($id);

            $response = ($this->deleteSeriesService)($requestDto);

            if ($response->success) {
                $this->addFlash('success', $response->message);
            } else {
                $this->addFlash('danger', $response->message);
            }

            return $this->redirectToRoute('series_list');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('series_list');
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error deleting series', [
                'seriesId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', 'An error occurred while deleting the series');

            return $this->redirectToRoute('series_list');
        }
    }
}
