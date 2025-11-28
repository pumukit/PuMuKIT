<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\Presenter;

use App\MultimediaObject\UI\Backoffice\Helpers\DurationFormat;
use App\MultimediaObject\UI\Backoffice\Helpers\StatusIcon;
use App\MultimediaObject\UI\Backoffice\Helpers\TypeIcon;
use App\Shared\UI\Backoffice\Helpers\BooleanIcon;
use App\Shared\UI\Backoffice\Helpers\DateFormat;
use App\Shared\UI\Backoffice\Helpers\TextTruncate;
use App\Shared\UI\Backoffice\Helpers\Thumbnail;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\RouterInterface;

/**
 * Presenter for MultimediaObject data table in Series context
 * Separates presentation logic from controller
 */
final class MultimediaObjectDataTablePresenter
{
    public function __construct(
        private RouterInterface $router
    ) {}

    public function present(MultimediaObject $multimediaObject, string $scheme, string $host): array
    {
        return [
            'id' => $multimediaObject->getId(),
            'thumbnail' => Thumbnail::convert($multimediaObject->getMainThumbnail($scheme, $host)),
            'title' => TextTruncate::long($multimediaObject->getTitle()),
            'status' => StatusIcon::convert($multimediaObject->getStatus()),
            'public_date' => DateFormat::format($multimediaObject->getPublicDate()),
            'record_date' => DateFormat::format($multimediaObject->getRecordDate()),
            'duration' => $multimediaObject->isVideoAudioType()
                ? DurationFormat::convert($multimediaObject->getDuration())
                : '---',
            'hide' => BooleanIcon::convert($multimediaObject->isHidden()),
            'type' => TypeIcon::convert($multimediaObject->getType()),
            'actions' => $this->renderActions($multimediaObject),
        ];
    }

    private function renderActions(MultimediaObject $multimediaObject): string
    {
        $viewUrl = $this->router->generate('multimedia_object_view', ['id' => $multimediaObject->getId()]);
        $deleteUrl = $this->router->generate('multimedia_object_delete', ['id' => $multimediaObject->getId()]);

        return sprintf(
            '<div class="d-flex gap-1 justify-content-end">
                <a href="%s" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View</a>
                <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this multimedia object?\');">
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

