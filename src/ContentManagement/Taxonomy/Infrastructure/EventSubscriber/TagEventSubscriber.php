<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Infrastructure\EventSubscriber;

use App\ContentManagement\Taxonomy\Domain\Event\TagCreated;
use App\ContentManagement\Taxonomy\Domain\Event\TagDeleted;
use App\ContentManagement\Taxonomy\Domain\Event\TagUpdated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class TagEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            TagCreated::class => 'onTagCreated',
            TagUpdated::class => 'onTagUpdated',
            TagDeleted::class => 'onTagDeleted',
        ];
    }

    public function onTagCreated(TagCreated $event): void
    {
        $this->logger->info('Tag created', [
            'tag_id' => $event->tagId,
            'cod' => $event->cod,
            'title' => $event->title,
            'parent_id' => $event->parentId,
            'occurred_on' => $event->occurredOn->format('c'),
        ]);

        // Aquí puedes añadir lógica adicional:
        // - Enviar notificaciones
        // - Actualizar cache
        // - Indexar en búsqueda
        // - Etc.
    }

    public function onTagUpdated(TagUpdated $event): void
    {
        $this->logger->info('Tag updated', [
            'tag_id' => $event->tagId,
            'cod' => $event->cod,
            'title' => $event->title,
            'occurred_on' => $event->occurredOn->format('c'),
        ]);

        // Lógica adicional para actualización:
        // - Invalidar cache
        // - Re-indexar en búsqueda
        // - Notificar cambios
    }

    public function onTagDeleted(TagDeleted $event): void
    {
        $this->logger->info('Tag deleted', [
            'tag_id' => $event->tagId,
            'cod' => $event->cod,
            'occurred_on' => $event->occurredOn->format('c'),
        ]);

        // Lógica adicional para eliminación:
        // - Limpiar cache
        // - Eliminar de índice de búsqueda
        // - Limpiar relaciones
    }
}
