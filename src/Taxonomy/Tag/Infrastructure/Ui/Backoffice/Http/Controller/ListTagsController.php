<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Infrastructure\Ui\Backoffice\Http\Controller;

use App\Taxonomy\Tag\Domain\Repository\TagRepositoryInterface;
use App\Taxonomy\Tag\Infrastructure\Ui\Backoffice\Http\Event\TagBulkOperationsEvent;
use App\Taxonomy\Tag\Infrastructure\Ui\Backoffice\Http\Event\TagListActionsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

final class ListTagsController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private TagRepositoryInterface $tagRepository
    ) {}

    public function __invoke(): Response
    {
        $bulkOperationsEvent = new TagBulkOperationsEvent();
        $this->eventDispatcher->dispatch($bulkOperationsEvent, TagBulkOperationsEvent::NAME);

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

        $listActionsEvent = new TagListActionsEvent();
        $this->eventDispatcher->dispatch($listActionsEvent, TagListActionsEvent::NAME);

        $customActions = $listActionsEvent->getActions();
        foreach ($customActions as &$action) {
            if ('route' === $action['type']) {
                $action['url'] = $this->generateUrl(
                    $action['url'],
                    $action['route_params'] ?? []
                );
            }
        }

        $rootTag = $this->tagRepository->findByCod('ROOT');
        $rootParentId = null !== $rootTag ? $rootTag->getId() : null;

        error_log('ROOT Tag found: '.(null !== $rootTag ? 'YES' : 'NO'));
        error_log('ROOT Tag ID: '.($rootParentId ?? 'NULL'));

        return $this->render('@Tag/Views/list.html.twig', [
            'bulkOperationsEvent' => $bulkOperationsEvent,
            'bulkOperations' => $operations,
            'listActionsEvent' => $listActionsEvent,
            'customActions' => $customActions,
            'rootParentId' => $rootParentId,
        ]);
    }
}
