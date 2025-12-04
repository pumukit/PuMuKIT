<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\Delete;

use App\Shared\Domain\EventBusInterface;
use App\Streaming\Domain\Event\ChannelDeletedEvent;
use App\Streaming\Domain\Exception\ChannelNotFoundException;
use App\Streaming\Domain\Repository\ChannelRepositoryInterface;

final class DeleteChannelService
{
    public function __construct(
        private readonly ChannelRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(DeleteChannelRequest $request): DeleteChannelResponse
    {
        $channel = $this->repository->find($request->id);

        if (!$channel) {
            throw new ChannelNotFoundException($request->id);
        }

        $channelId = $channel->getId();

        $this->repository->delete($channel);

        $this->eventBus->dispatch(
            new ChannelDeletedEvent($channelId),
            ChannelDeletedEvent::NAME
        );

        return new DeleteChannelResponse($channelId);
    }
}
