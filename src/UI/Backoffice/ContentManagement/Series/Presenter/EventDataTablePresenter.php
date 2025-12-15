<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Series\Presenter;

use App\Shared\Infrastructure\Ui\Backoffice\Helpers\TextTruncate;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class EventDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
        private string $scheme,
        private string $host,
    ) {}

    public function present(MultimediaObject $event): array
    {
        return [
            'id' => $event->getId(),
            'thumbnail' => $this->renderThumbnail($event),
            'title' => TextTruncate::long($event->getTitle()),
            'actions' => $this->renderActions($event),
        ];
    }

    private function renderThumbnail(MultimediaObject $event): string
    {
        $thumbnail = $event->getMainThumbnail($this->scheme, $this->host);

        return $this->twig->render('@Shared/Views/components/table/_thumbnail.html.twig', [
            'thumbnail' => htmlspecialchars($thumbnail),
            'defaultImage' => 'images/default_streaming.svg',
            'title' => htmlspecialchars($event->getTitle()),
        ]);
    }

    private function renderActions(MultimediaObject $event): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('multimedia_object_view', ['id' => $event->getId()]),
        ]);

        $editButton = $this->twig->render('@Shared/Views/components/table/buttons/_custom_button.html.twig', [
            'url' => '#',
            'icon' => 'fa-edit',
            'style' => 'warning',
            'title' => 'Edit',
            'type' => 'link',
        ]);

        $deleteButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => $this->router->generate('multimedia_object_delete', ['id' => $event->getId()]),
            'confirm' => 'Are you sure you want to delete this multimedia object?',
        ]);

        return $viewButton.$editButton.$deleteButton;
    }
}
