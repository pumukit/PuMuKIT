<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Streaming\Presenter;

use Pumukit\SchemaBundle\Document\Live;
use Symfony\Component\Routing\RouterInterface;

final class ChannelDataTablePresenter
{
    public function __construct(private readonly RouterInterface $router) {}

    public function present(Live $channel, string $locale = 'en'): array
    {
        return [
            'id' => $channel->getId(),
            'name' => $channel->getName($locale),
            'url' => $channel->getUrl(),
            'source_name' => $channel->getSourceName(),
            'live_type' => $channel->getLiveType(),
            'broadcasting' => $this->renderBooleanBadge($channel->getBroadcasting()),
            'index_play' => $this->renderBooleanBadge($channel->getIndexPlay()),
            'chat' => $this->renderBooleanBadge($channel->isChat()),
            'actions' => $this->renderActions($channel),
        ];
    }

    private function renderActions(Live $channel): string
    {
        $viewUrl = $this->router->generate('streaming_channel_view', ['id' => $channel->getId()]);
        $editUrl = $this->router->generate('streaming_channel_edit', ['id' => $channel->getId()]);
        $deleteUrl = $this->router->generate('streaming_channel_delete', ['id' => $channel->getId()]);

        return sprintf(
            '<div class="d-flex gap-1 justify-content-end">
                <a href="%s" class="btn btn-sm btn-info" title="Ver"><i class="fa fa-eye"></i> View</a>
                <a href="%s" class="btn btn-sm btn-warning" title="Editar"><i class="fa fa-edit"></i> Update</a>
                <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'¿Está seguro de que desea eliminar este canal?\');">
                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                        <i class="fa fa-trash"></i> Delete
                    </button>
                </form>
            </div>',
            $viewUrl,
            $editUrl,
            $deleteUrl
        );
    }

    private function renderBooleanBadge(bool $value): string
    {
        if ($value) {
            return '<span class="badge bg-success"><i class="fa fa-check"></i> Sí</span>';
        }

        return '<span class="badge bg-secondary"><i class="fa fa-times"></i> No</span>';
    }
}
