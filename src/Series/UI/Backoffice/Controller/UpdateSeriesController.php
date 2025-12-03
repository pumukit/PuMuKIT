<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\Controller;

use App\Series\Application\Update\UpdateSeriesService;
use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Series\UI\Backoffice\Event\SeriesFormSubmitEvent;
use App\Series\UI\Backoffice\FormHandler\UpdateSeriesFormHandler;
use App\Shared\Domain\LoggerInterface;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateSeriesController extends AbstractController
{
    public function __construct(
        private readonly UpdateSeriesService $updateSeriesService,
        private readonly UpdateSeriesFormHandler $formHandler,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        try {
            $dto = $this->formHandler->handleRequest($request, $id);

            $response = ($this->updateSeriesService)($dto);

            $submitEvent = new SeriesFormSubmitEvent($response->series, $request->request->all());
            $this->eventDispatcher->dispatch($submitEvent, SeriesFormSubmitEvent::NAME);

            if ($submitEvent->hasErrors()) {
                foreach ($submitEvent->getErrors() as $field => $errors) {
                    foreach ($errors as $error) {
                        $this->addFlash('danger', $this->translator->trans(
                            'series.form.error',
                            ['%field%' => $field, '%error%' => $error],
                            'series'
                        ));
                    }
                }
            } else {
                $this->addFlash('success', $this->translator->trans(
                    'series.flash.updated',
                    ['%title%' => $response->series->getTitle()],
                    'series'
                ));
            }
        } catch (SeriesNotFoundException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getMessage(), [], 'series'));

            return $this->redirectToRoute('series_list');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getMessage(), [], 'series'));
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error updating series', [
                'seriesId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', $this->translator->trans('series.error.unexpected_update', [], 'series'));
        }

        return $this->redirectToRoute('series_view', ['id' => $id, 'tab' => 'edit']);
    }
}
