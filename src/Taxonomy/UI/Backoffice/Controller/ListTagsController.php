<?php

declare(strict_types=1);

namespace App\Taxonomy\UI\Backoffice\Controller;

use App\Taxonomy\Domain\Repository\TagRepositoryInterface;
use App\Taxonomy\UI\Backoffice\Event\TagBulkOperationsEvent;
use App\Taxonomy\UI\Backoffice\Event\TagListActionsEvent;
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
                // Generar URL para operaciones bulk (sin parámetros dinámicos)
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

        // Buscar el tag ROOT para obtener su ID
        $rootTag = $this->tagRepository->findByCod('ROOT');
        $rootParentId = $rootTag ? $rootTag->getId() : null;

        // Log para debug (remover después de verificar)
        error_log("ROOT Tag found: " . ($rootTag ? 'YES' : 'NO'));
        error_log("ROOT Tag ID: " . ($rootParentId ?? 'NULL'));

        return $this->render('@Taxonomy/UI/Backoffice/Views/list.html.twig', [
            'bulkOperationsEvent' => $bulkOperationsEvent,
            'bulkOperations' => $operations,
            'listActionsEvent' => $listActionsEvent,
            'customActions' => $customActions,
            'rootParentId' => $rootParentId,
        ]);
    }
}

