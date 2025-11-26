<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\Update\UpdateSeriesRequest;
use App\Series\Application\Update\UpdateSeriesService;
use App\Series\UI\Backend\Event\SeriesFormSubmitEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateSeriesController extends AbstractController
{
    public function __construct(
        private UpdateSeriesService $updateSeriesService,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        try {
            $title = $request->request->all('title');
            $subtitle = $request->request->all('subtitle');
            $description = $request->request->all('description');
            $header = $request->request->all('header');
            $footer = $request->request->all('footer');
            $keywords = $request->request->all('keywords');
            $comments = $request->request->get('comments');
            $announce = null !== $request->request->get('announce');
            $hide = null !== $request->request->get('hide');
            $sorting = $request->request->get('sorting');
            $publicDate = $request->request->get('publicDate');

            $title = !empty($title) ? $title : null;
            $subtitle = !empty($subtitle) ? $subtitle : null;
            $description = !empty($description) ? $description : null;
            $header = !empty($header) ? $header : null;
            $footer = !empty($footer) ? $footer : null;
            $keywords = !empty($keywords) ? $keywords : null;

            $dto = new UpdateSeriesRequest(
                id: $id,
                title: $title,
                subtitle: $subtitle,
                description: $description,
                header: $header,
                footer: $footer,
                comments: $comments,
                keywords: $keywords,
                announce: $announce,
                hide: $hide,
                publicDate: $publicDate ? new \DateTime($publicDate) : null,
                sorting: $sorting ? (int) $sorting : null,
                seriesTypeId: null,
                seriesStyleId: null,
                properties: null
            );

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
