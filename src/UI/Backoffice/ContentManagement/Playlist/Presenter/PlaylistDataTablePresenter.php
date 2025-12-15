<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Playlist\Presenter;

use App\ContentManagement\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use App\Shared\Infrastructure\Ui\Backoffice\Helpers\BooleanIcon;
use App\Shared\Infrastructure\Ui\Backoffice\Helpers\DateFormat;
use App\Shared\Infrastructure\Ui\Backoffice\Helpers\TextTruncate;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class PlaylistDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private PlaylistRepositoryInterface $playlistRepository,
        private Environment $twig,
        private string $scheme,
        private string $host,
    ) {}

    public function present(Series $playlist): array
    {
        return [
            'id' => $playlist->getId(),
            'title' => TextTruncate::long($playlist->getTitle()),
            'hide' => BooleanIcon::convert($playlist->getHide()),
            'announce' => BooleanIcon::convert($playlist->getAnnounce()),
            'public_date' => DateFormat::format($playlist->getPublicDate()),
            'thumbnail' => $this->renderThumbnail($playlist),
            'objectCount' => $this->playlistRepository->countMultimediaObjects($playlist->getId()),
            'actions' => $this->renderActions($playlist),
        ];
    }

    private function renderThumbnail(Series $playlist): string
    {
        $thumbnail = $playlist->getMainThumbnail($this->scheme, $this->host);

        return $this->twig->render('@Shared/Views/components/table/_thumbnail.html.twig', [
            'thumbnail' => htmlspecialchars($thumbnail),
            'defaultImage' => 'images/default_playlist.svg',
            'title' => htmlspecialchars($playlist->getTitle()),
        ]);
    }

    private function renderActions(Series $playlist): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('playlist_view', ['id' => $playlist->getId()]),
        ]);

        $editButton = $this->twig->render('@Shared/Views/components/table/buttons/_edit_button.html.twig', [
            'url' => $this->router->generate('playlist_view', ['id' => $playlist->getId(), 'tab' => 'edit']),
        ]);

        $deleteButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => $this->router->generate('playlist_delete', ['id' => $playlist->getId()]),
            'confirm' => 'Are you sure you want to delete this playlist?',
        ]);

        return $viewButton.$editButton.$deleteButton;
    }
}
