<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Pumukit\SchemaBundle\Services\PersonalSeriesService;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class PersonalSeriesListener
{
    private $personalSeriesService;

    public function __construct(PersonalSeriesService $personalSeriesService)
    {
        $this->personalSeriesService = $personalSeriesService;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $this->personalSeriesService->find();
    }
}
