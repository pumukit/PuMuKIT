<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\Presenter;

use App\Series\Domain\Repository\SeriesRepositoryInterface;
use App\Shared\UI\Backoffice\Helpers\BooleanIcon;
use App\Shared\UI\Backoffice\Helpers\DateFormat;
use App\Shared\UI\Backoffice\Helpers\TextTruncate;
use App\Shared\UI\Backoffice\Helpers\Thumbnail;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Routing\RouterInterface;

final class SeriesDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private SeriesRepositoryInterface $seriesRepository
    ) {}

    public function present(Series $series, string $scheme, string $host, string $locale): array
    {
        return [
            'id' => $series->getId(),
            'title' => TextTruncate::long($series->getTitle()),
            'hide' => BooleanIcon::convert($series->getHide()),
            'announce' => BooleanIcon::convert($series->getAnnounce()),
            'public_date' => DateFormat::format($series->getPublicDate()),
            'thumbnail' => Thumbnail::convert($series->getMainThumbnail($scheme, $host)),
            'objectCount' => $this->seriesRepository->countMultimediaObjects($series->getId()),
            'eventCount' => $this->seriesRepository->countEventMultimediaObjects($series->getId()),
            'actions' => $this->renderActions($series),
        ];
    }

    private function renderActions(Series $series): string
    {
        $viewUrl = $this->router->generate('series_view', ['id' => $series->getId()]);
        $cloneUrl = $this->router->generate('series_clone', ['id' => $series->getId()]);
        $deleteUrl = $this->router->generate('series_delete', ['id' => $series->getId()]);

        return sprintf(
            '<div class="d-flex gap-1 justify-content-end">
                <a href="%s" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View</a>
                <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to clone this series?\');">
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="fa fa-copy"></i> Clone
                    </button>
                </form>
                <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this series?\');">
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fa fa-trash"></i> Delete
                    </button>
                </form>
            </div>',
            $viewUrl,
            $cloneUrl,
            $deleteUrl
        );
    }
}

