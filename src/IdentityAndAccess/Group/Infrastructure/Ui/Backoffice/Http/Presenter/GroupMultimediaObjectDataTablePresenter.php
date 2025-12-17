<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Presenter;

use App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers\TextTruncate;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class GroupMultimediaObjectDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
    ) {}

    public function present(array $multimediaObjectData, string $locale): array
    {
        return [
            'id' => $multimediaObjectData['_id'],
            'title' => TextTruncate::long($multimediaObjectData['title'][$locale]),
            'actions' => $this->renderActions($multimediaObjectData),
        ];
    }

    private function renderActions(array $multimediaObjectData): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('multimedia_object_view', ['id' => $multimediaObjectData['_id']]),
            'target' => '_blank',
        ]);

        $viewSeriesButton = $this->twig->render('@Shared/Views/components/table/buttons/_custom_button.html.twig', [
            'url' => $this->router->generate('series_view', ['id' => $multimediaObjectData['series']]),
            'icon' => 'fa-eye',
            'style' => 'info',
            'title' => 'View Series',
            'type' => 'link',
        ]);

        return $viewButton.$viewSeriesButton;
    }
}
