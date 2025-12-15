<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Infrastructure\Ui\Backoffice\Http\Controller;

use Pumukit\SchemaBundle\Document\User;
use App\ContentManagement\Series\Application\Create\CreateSeriesRequest;
use App\ContentManagement\Series\Application\Create\CreateSeriesService;
use App\Shared\Domain\LoggerInterface;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class CreateSeriesController extends AbstractController
{
    public function __construct(
        private readonly CreateSeriesService $createSeriesService,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator
    ) {}

    public function __invoke(): RedirectResponse
    {
        try {
            $user = $this->getUser();

            if (!$user instanceof User) {
                throw new \RuntimeException('Invalid user type');
            }

            $request = new CreateSeriesRequest($user->getId());

            $response = ($this->createSeriesService)($request);

            $this->addFlash('success', $this->translator->trans(
                'series.flash.created',
                ['%title%' => $response->series->getTitle()],
                'series'
            ));

            return $this->redirectToRoute('series_view', ['id' => $response->series->getId()]);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getMessage(), [], 'series'));

            return $this->redirectToRoute('series_list');
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error creating series', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', $this->translator->trans('series.error.unexpected', [], 'series'));

            return $this->redirectToRoute('series_list');
        }
    }
}
