<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\EventSubscriber;

use App\Series\Domain\Repository\SeriesRepositoryInterface;
use App\Series\UI\Backoffice\Event\SeriesFormBuildEvent;
use App\Series\UI\Backoffice\Event\SeriesFormSubmitEvent;
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
