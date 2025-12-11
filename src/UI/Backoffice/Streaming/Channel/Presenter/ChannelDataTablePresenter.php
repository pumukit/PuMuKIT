<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Streaming\Channel\Presenter;

use App\UI\Backoffice\Streaming\Channel\Helpers\StatusText;
use Pumukit\SchemaBundle\Document\Live;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class ChannelDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
    ) {}

    public function present(Live $channel, string $locale = 'en'): array
    {
        return [
            'id' => $channel->getId(),
            'name' => $channel->getName($locale),
            'url' => $channel->getUrl(),
            'source_name' => $channel->getSourceName(),
            'live_type' => $channel->getLiveType(),
            'broadcasting' => StatusText::convert($channel->getBroadcasting()),
            'actions' => $this->renderActions($channel),
        ];
    }

    private function renderActions(Live $channel): string
    {
        return $this->twig->render('@Shared/Views/components/table/_datatable_actions.html.twig', [
            'actions' => [
                [
                    'type' => 'link',
                    'url' => $this->router->generate('streaming_channel_view', ['id' => $channel->getId()]),
                    'style' => 'info',
                    'icon' => 'eye',
                    'title' => 'View',
                ],
                [
                    'type' => 'link',
                    'url' => $this->router->generate('streaming_channel_edit', ['id' => $channel->getId()]),
                    'style' => 'warning',
                    'icon' => 'edit',
                    'title' => 'Edit',
                ],
                [
                    'type' => 'form',
                    'url' => $this->router->generate('streaming_channel_delete', ['id' => $channel->getId()]),
                    'style' => 'danger',
                    'icon' => 'trash',
                    'title' => 'Delete',
                    'confirm' => 'Are you sure you want to delete this channel?',
                ],
            ],
        ]);
    }
}
