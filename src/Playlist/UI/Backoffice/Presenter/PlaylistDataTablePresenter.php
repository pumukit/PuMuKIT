<?php

declare(strict_types=1);

namespace App\Playlist\UI\Backoffice\Presenter;

use App\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use App\Shared\UI\Backoffice\Helpers\BooleanIcon;
use App\Shared\UI\Backoffice\Helpers\DateFormat;
use App\Shared\UI\Backoffice\Helpers\TextTruncate;
use App\Shared\UI\Backoffice\Helpers\Thumbnail;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Routing\RouterInterface;

final class PlaylistDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private PlaylistRepositoryInterface $playlistRepository
    ) {}

    public function present(Series $playlist, string $scheme, string $host, string $locale): array
    {
        return [
            'id' => $playlist->getId(),
            'title' => TextTruncate::long($playlist->getTitle()),
            'hide' => BooleanIcon::convert($playlist->getHide()),
            'announce' => BooleanIcon::convert($playlist->getAnnounce()),
            'public_date' => DateFormat::format($playlist->getPublicDate()),
            'thumbnail' => Thumbnail::convert($playlist->getMainThumbnail($scheme, $host)),
            'objectCount' => $this->playlistRepository->countMultimediaObjects($playlist->getId()),
            'actions' => $this->renderActions($playlist),
        ];
    }

    private function renderActions(Series $playlist): string
    {
        $viewUrl = $this->router->generate('playlist_view', ['id' => $playlist->getId()]);
        $deleteUrl = $this->router->generate('playlist_delete', ['id' => $playlist->getId()]);

        return sprintf(
            '<div class="d-flex gap-1 justify-content-end">
                <a href="%s" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View</a>
                <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this playlist?\');">
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fa fa-trash"></i> Delete
                    </button>
                </form>
            </div>',
            $viewUrl,
            $deleteUrl
        );
    }
}
