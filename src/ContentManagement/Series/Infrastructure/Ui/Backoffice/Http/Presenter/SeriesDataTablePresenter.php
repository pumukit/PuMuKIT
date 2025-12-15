<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Infrastructure\Ui\Backoffice\Http\Presenter;

use App\ContentManagement\Series\Domain\Repository\SeriesRepositoryInterface;
use App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers\BooleanIcon;
use App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers\DateFormat;
use App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers\TextTruncate;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class SeriesDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
        private SeriesRepositoryInterface $seriesRepository,
        private string $scheme,
        private string $host,
    ) {}

    public function present(Series $series): array
    {
        return [
            'id' => $series->getId(),
            'title' => TextTruncate::long($series->getTitle()),
            'hide' => BooleanIcon::convert($series->getHide()),
            'announce' => BooleanIcon::convert($series->getAnnounce()),
            'public_date' => DateFormat::format($series->getPublicDate()),
            'thumbnail' => $this->renderThumbnail($series),
            'objectCount' => $this->seriesRepository->countMultimediaObjects($series->getId()),
            'eventCount' => $this->seriesRepository->countEventMultimediaObjects($series->getId()),
            'actions' => $this->renderActions($series),
        ];
    }

    private function renderThumbnail(Series $series): string
    {
        $thumbnail = $series->getMainThumbnail($this->scheme, $this->host);

        return $this->twig->render('@Shared/Views/components/table/_thumbnail.html.twig', [
            'thumbnail' => htmlspecialchars($thumbnail),
            'defaultImage' => 'images/default_series.svg',
            'title' => htmlspecialchars($series->getTitle()),
        ]);
    }

    private function renderActions(Series $series): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('series_view', ['id' => $series->getId()]),
        ]);

        $editButton = $this->twig->render('@Shared/Views/components/table/buttons/_edit_button.html.twig', [
            'url' => $this->router->generate('series_clone', ['id' => $series->getId()]),
        ]);

        $deleteButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => $this->router->generate('series_delete', ['id' => $series->getId()]),
            'confirm' => 'Are you sure you want to delete this series?',
        ]);

        return $viewButton.$editButton.$deleteButton;
    }
}
