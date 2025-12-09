<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Playlist\Presenter;

use App\ContentManagement\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use App\UI\Backoffice\Shared\Helpers\BooleanIcon;
use App\UI\Backoffice\Shared\Helpers\DateFormat;
use App\UI\Backoffice\Shared\Helpers\TextTruncate;
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
        return $this->twig->render('@Shared/Views/components/table/_datatable_actions.html.twig', [
            'actions' => [
                [
                    'type' => 'link',
                    'url' => $this->router->generate('multimedia_object_view', ['id' => $playlist->getId()]),
                    'style' => 'info',
                    'icon' => 'eye',
                    'title' => 'View',
                ],
                ['type' => 'link', 'url' => '#', 'style' => 'warning', 'icon' => 'edit', 'title' => 'Edit'],
                [
                    'type' => 'form',
                    'url' => $this->router->generate('multimedia_object_delete', ['id' => $playlist->getId()]),
                    'style' => 'danger',
                    'icon' => 'trash',
                    'title' => 'Delete',
                    'confirm' => 'Are you sure you want to delete this playlist?',
                ],
            ],
        ]);
    }
}
