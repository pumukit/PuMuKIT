<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\MultimediaObject\Presenter;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\RouterInterface;

final class MultimediaObjectDataTablePresenter
{
    public function __construct(private readonly RouterInterface $router) {}

    public function present(MultimediaObject $multimediaObject, string $locale = 'en'): array
    {
        return [
            'id' => $multimediaObject->getId(),
            'thumbnail' => $this->renderThumbnail($multimediaObject),
            'title' => $multimediaObject->getTitle($locale),
            'series' => $multimediaObject->getSeries() ? $multimediaObject->getSeries()->getTitle($locale) : '',
            'status' => $this->renderStatus($multimediaObject->getStatus()),
            'public_date' => $multimediaObject->getPublicDate() ? $multimediaObject->getPublicDate()->format('Y-m-d H:i') : '',
            'duration' => $this->formatDuration($multimediaObject->getDuration()),
            'actions' => $this->renderActions($multimediaObject),
        ];
    }

    private function renderThumbnail(MultimediaObject $multimediaObject): string
    {
        $picUrl = $multimediaObject->getPic() ? $multimediaObject->getPic()->getUrl() : '/bundles/pumukitnewadmin/images/no_image.jpg';

        return sprintf(
            '<img src="%s" alt="%s" style="width: 60px; height: 40px; object-fit: cover;" />',
            htmlspecialchars($picUrl),
            htmlspecialchars($multimediaObject->getTitle())
        );
    }

    private function renderStatus(int $status): string
    {
        $statusMap = [
            MultimediaObject::STATUS_PUBLISHED => ['label' => 'Published', 'class' => 'success'],
            MultimediaObject::STATUS_BLOCKED => ['label' => 'Blocked', 'class' => 'danger'],
            MultimediaObject::STATUS_HIDDEN => ['label' => 'Hidden', 'class' => 'warning'],
            MultimediaObject::STATUS_NEW => ['label' => 'New', 'class' => 'info'],
        ];

        $statusInfo = $statusMap[$status] ?? ['label' => 'Unknown', 'class' => 'secondary'];

        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            $statusInfo['class'],
            $statusInfo['label']
        );
    }

    private function formatDuration(?int $duration): string
    {
        if (!$duration) {
            return '-';
        }

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    private function renderActions(MultimediaObject $multimediaObject): string
    {
        $viewUrl = '#';
        $editUrl = '#';
        $deleteUrl = $this->router->generate('multimedia_object_delete', ['id' => $multimediaObject->getId()]);

        return sprintf(
            '<div class="d-flex gap-1 justify-content-end">
                <a href="%s" class="btn btn-sm btn-info" title="View"><i class="fa fa-eye"></i></a>
                <a href="%s" class="btn btn-sm btn-warning" title="Edit"><i class="fa fa-edit"></i></a>
                <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this multimedia object?\');">
                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>
                </form>
            </div>',
            $viewUrl,
            $editUrl,
            $deleteUrl
        );
    }
}
