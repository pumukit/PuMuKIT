<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ui\Backoffice\Http\Controller;

use App\Shared\Infrastructure\Ui\Backoffice\Http\Event\DashboardBuildEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DashboardController extends AbstractController
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(): Response
    {
        $event = new DashboardBuildEvent();

        $this->eventDispatcher->dispatch($event, DashboardBuildEvent::NAME);

        return $this->render('@Shared/Views/dashboard.html.twig', [
            'widgets' => $event->getWidgets(),
        ]);
    }
}
