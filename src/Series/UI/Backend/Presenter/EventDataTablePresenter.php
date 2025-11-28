<?php

declare(strict_types=1);

namespace App\Series\UI\Backend\Presenter;

use App\Shared\UI\Backend\Helpers\Thumbnail;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\RouterInterface;

/**
 * Presenter for Event (Live MultimediaObject) data table in Series context
 * Separates presentation logic from controller
 */
final class EventDataTablePresenter
{
    public function __construct(
        private RouterInterface $router
    ) {}

    public function present(MultimediaObject $event, string $scheme, string $host): array
    {
        return [
            'id' => $event->getId(),
            'thumbnail' => Thumbnail::convert($event->getMainThumbnail($scheme, $host)),
            'title' => $event->getTitle(),
            'actions' => $this->renderActions($event),
        ];
    }

    private function renderActions(MultimediaObject $event): string
    {
        // TODO: Update URLs when routes are available for live events
        $viewUrl = '#'; // $this->router->generate('live_event_view', ['id' => $event->getId()]);
        $deleteUrl = '#'; // $this->router->generate('live_event_delete', ['id' => $event->getId()]);

        return sprintf(
            '<div class="d-flex gap-1 justify-content-end">
                <a href="%s" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View</a>
                <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this event?\');">
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

