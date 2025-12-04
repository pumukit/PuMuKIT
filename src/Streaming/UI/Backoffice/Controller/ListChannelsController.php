<?php

declare(strict_types=1);

namespace App\Streaming\UI\Backoffice\Controller;

use App\Streaming\UI\Backoffice\Event\ChannelBulkOperationsEvent;
use App\Streaming\UI\Backoffice\Event\ChannelListActionsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ListChannelsController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(): Response
    {
        $bulkOperationsEvent = new ChannelBulkOperationsEvent();
        $this->eventDispatcher->dispatch($bulkOperationsEvent, ChannelBulkOperationsEvent::NAME);

        $operations = $bulkOperationsEvent->getOperations();
        foreach ($operations as &$operation) {
            if ('route' === $operation['type']) {
                if ('#' === $operation['handler']) {
                    continue;
                }
                $operation['handler'] = $this->generateUrl(
                    $operation['handler'],
                    $operation['route_params'] ?? []
                );
                $operation['type'] = 'url';
            }
        }

        $listActionsEvent = new ChannelListActionsEvent();
        $this->eventDispatcher->dispatch($listActionsEvent, ChannelListActionsEvent::NAME);

        $customActions = $listActionsEvent->getActions();
        foreach ($customActions as &$action) {
            if ('route' === $action['type']) {
                $action['url'] = $this->generateUrl(
                    $action['url'],
                    $action['route_params'] ?? []
                );
            }
        }

        return $this->render('@Streaming/UI/Backoffice/Views/list.html.twig', [
            'bulkOperationsEvent' => $bulkOperationsEvent,
            'bulkOperations' => $operations,
            'listActionsEvent' => $listActionsEvent,
            'customActions' => $customActions,
        ]);
    }
}
