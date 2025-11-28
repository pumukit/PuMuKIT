<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\Update\UpdateSeriesRequest;
use App\Series\Application\Update\UpdateSeriesService;
use App\Series\UI\Backend\FormHandler\UpdateSeriesFormHandler;
use App\Series\UI\Backend\Event\SeriesFormSubmitEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateSeriesController extends AbstractController
{
    public function __construct(
        private UpdateSeriesService $updateSeriesService,
        private UpdateSeriesFormHandler $formHandler,
        private EventDispatcherInterface $eventDispatcher
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
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('Error al actualizar la serie: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('series_view', ['id' => $id, 'tab' => 'edit']);
    }
}
