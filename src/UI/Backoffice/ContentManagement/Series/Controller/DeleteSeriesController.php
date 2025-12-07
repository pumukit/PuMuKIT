<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Series\Controller;

use App\ContentManagement\Series\Application\Delete\DeleteSeriesRequest;
use App\ContentManagement\Series\Application\Delete\DeleteSeriesService;
use App\Shared\Domain\LoggerInterface;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class DeleteSeriesController extends AbstractController
{
    public function __construct(
        private readonly DeleteSeriesService $deleteSeriesService,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator
    ) {}

    public function __invoke(string $id): RedirectResponse
    {
        try {
            $requestDto = new DeleteSeriesRequest($id);

            $response = ($this->deleteSeriesService)($requestDto);

            if ($response->success) {
                $this->addFlash('success', $this->translator->trans($response->message, [], 'series'));
            } else {
                $this->addFlash('danger', $this->translator->trans($response->message, [], 'series'));
            }

            return $this->redirectToRoute('series_list');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getMessage(), [], 'series'));

            return $this->redirectToRoute('series_list');
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error deleting series', [
                'seriesId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', $this->translator->trans('series.error.unexpected_delete', [], 'series'));

            return $this->redirectToRoute('series_list');
        }
    }
}
