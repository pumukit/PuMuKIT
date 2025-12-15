<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Series\Presenter;

use App\UI\Backoffice\ContentManagement\MultimediaObject\Helpers\DurationFormat;
use App\UI\Backoffice\ContentManagement\MultimediaObject\Helpers\StatusIcon;
use App\UI\Backoffice\ContentManagement\MultimediaObject\Helpers\TypeIcon;
use App\Shared\Infrastructure\Ui\Backoffice\Helpers\BooleanIcon;
use App\Shared\Infrastructure\Ui\Backoffice\Helpers\DateFormat;
use App\Shared\Infrastructure\Ui\Backoffice\Helpers\TextTruncate;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class MultimediaObjectDataTablePresenter
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
            'defaultImage' => 'images/no_image.svg',
            'title' => htmlspecialchars($multimediaObject->getTitle()),
        ]);
    }

    private function renderActions(MultimediaObject $multimediaObject): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('multimedia_object_view', ['id' => $multimediaObject->getId()]),
        ]);

        $editButton = $this->twig->render('@Shared/Views/components/table/buttons/_edit_button.html.twig', [
            'url' => $this->router->generate('multimedia_object_view', [
                'id' => $multimediaObject->getId(),
                'tab' => 'publication',
            ]),
        ]);

        $deleteButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => $this->router->generate('multimedia_object_delete', ['id' => $multimediaObject->getId()]),
            'confirm' => 'Are you sure you want to delete this multimedia object?',
        ]);

        return $viewButton.$editButton.$deleteButton;
    }
}
