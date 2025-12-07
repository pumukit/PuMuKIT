<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Series\EventSubscriber;

use App\ContentManagement\Series\Domain\Repository\SeriesRepositoryInterface;
use App\UI\Backoffice\ContentManagement\Series\Event\SeriesFormBuildEvent;
use App\UI\Backoffice\ContentManagement\Series\Event\SeriesFormSubmitEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SeriesFormFieldsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SeriesRepositoryInterface $seriesRepository
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SeriesFormBuildEvent::NAME => 'onFormBuild',
            SeriesFormSubmitEvent::NAME => 'onFormSubmit',
        ];
    }

    public function onFormBuild(SeriesFormBuildEvent $event): void
    {
        // TODO: We can add all fields using subscriber instead defined on twig template.
    }

    public function onFormSubmit(SeriesFormSubmitEvent $event): void
    {
        // TODO: We can process all fields using subscriber instead defined on controller.
    }
}
