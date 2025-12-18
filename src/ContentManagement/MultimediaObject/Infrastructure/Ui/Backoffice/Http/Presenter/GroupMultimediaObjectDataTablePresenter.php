<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Infrastructure\Ui\Backoffice\Http\Presenter;

use App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers\TextTruncate;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class GroupMultimediaObjectDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
    ) {}

    public function present(MultimediaObject $multimediaObject, string $locale): array
    {
        return [
            'id' => $multimediaObject->getId(),
            'title' => TextTruncate::long($multimediaObject->getTitle()),
            'actions' => $this->renderActions($multimediaObject),
        ];
    }

    private function renderActions(MultimediaObject $multimediaObject): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('multimedia_object_view', ['id' => $multimediaObject->getId()]),
            'target' => '_blank',
        ]);

        $viewSeriesButton = $this->twig->render('@Shared/Views/components/table/buttons/_custom_button.html.twig', [
            'url' => $this->router->generate('series_view', ['id' => $multimediaObject->getSeries()]),
            'icon' => 'fa-eye',
            'style' => 'info',
            'title' => 'View Series',
            'type' => 'link',
        ]);

        return $viewButton.$viewSeriesButton;
    }
}
