<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\Controller;

use App\Series\Application\Update\UpdateSeriesService;
use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Series\UI\Backoffice\Event\SeriesFormSubmitEvent;
use App\Series\UI\Backoffice\FormHandler\UpdateSeriesFormHandler;
use App\Shared\Domain\LoggerInterface;
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
        private readonly LoggerInterface $logger
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
                        $this->addFlash('danger', sprintf('%s: %s', $field, $error));
                    }
                }
            } else {
                $this->addFlash('success', sprintf('Serie "%s" actualizada correctamente', $response->series->getTitle()));
            }
        } catch (SeriesNotFoundException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('series_list');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error updating series', [
                'seriesId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', 'Error al actualizar la serie');
        }

        return $this->redirectToRoute('series_view', ['id' => $id, 'tab' => 'edit']);
    }
}
