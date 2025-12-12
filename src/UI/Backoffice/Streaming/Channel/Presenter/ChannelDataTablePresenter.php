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
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('streaming_channel_view', ['id' => $channel->getId()]),
        ]);

        $editButton = $this->twig->render('@Shared/Views/components/table/buttons/_edit_button.html.twig', [
            'url' => $this->router->generate('streaming_channel_edit', ['id' => $channel->getId()]),
        ]);

        $deleteButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => $this->router->generate('streaming_channel_delete', ['id' => $channel->getId()]),
            'confirm' => 'Are you sure you want to delete this channel?',
        ]);

        return $viewButton . $editButton . $deleteButton;
    }
}
