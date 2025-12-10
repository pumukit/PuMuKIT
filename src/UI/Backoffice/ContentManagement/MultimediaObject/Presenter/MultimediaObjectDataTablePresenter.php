<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\MultimediaObject\Presenter;

use App\UI\Backoffice\ContentManagement\MultimediaObject\Helpers\DurationFormat;
use App\UI\Backoffice\ContentManagement\MultimediaObject\Helpers\StatusIcon;
use App\UI\Backoffice\ContentManagement\MultimediaObject\Helpers\TypeIcon;
use App\UI\Backoffice\Shared\Helpers\BooleanIcon;
use App\UI\Backoffice\Shared\Helpers\DateFormat;
use App\UI\Backoffice\Shared\Helpers\TextTruncate;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class MultimediaObjectDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
        private string $scheme,
        private string $host,
    ) {}

    public function present(MultimediaObject $multimediaObject): array
    {
        return [
            'id' => $multimediaObject->getId(),
            'thumbnail' => $this->renderThumbnail($multimediaObject),
            'series' => TextTruncate::long($multimediaObject->getSeries()->getTitle()),
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

    private function renderThumbnail(MultimediaObject $multimediaObject): string
    {
        $thumbnail = $multimediaObject->getMainThumbnail($this->scheme, $this->host);

        return $this->twig->render('@Shared/Views/components/table/_thumbnail.html.twig', [
            'thumbnail' => htmlspecialchars($thumbnail),
            'defaultImage' => 'images/default_multimedia_object.svg',
            'title' => htmlspecialchars($multimediaObject->getTitle()),
        ]);
    }

    private function renderActions(MultimediaObject $multimediaObject): string
    {
        return $this->twig->render('@Shared/Views/components/table/_datatable_actions.html.twig', [
            'actions' => [
                [
                    'type' => 'link',
                    'url' => $this->router->generate('multimedia_object_view', ['id' => $multimediaObject->getId()]),
                    'style' => 'info',
                    'icon' => 'eye',
                    'title' => 'View',
                ],
                [
                    'type' => 'link',
                    'url' => $this->router->generate('multimedia_object_view', ['id' => $multimediaObject->getId(), 'tab' => 'publication']),
                    'style' => 'warning',
                    'icon' => 'edit',
                    'title' => 'Edit'
                ],
                [
                    'type' => 'form',
                    'url' => $this->router->generate('multimedia_object_delete', ['id' => $multimediaObject->getId()]),
                    'style' => 'danger',
                    'icon' => 'trash',
                    'title' => 'Delete',
                    'confirm' => 'Are you sure you want to delete this multimedia object?',
                ],
            ],
        ]);
    }
}
