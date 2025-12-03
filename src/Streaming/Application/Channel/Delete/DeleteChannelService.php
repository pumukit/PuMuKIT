<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\Delete;

use App\Streaming\Domain\Event\ChannelDeletedEvent;
use App\Streaming\Domain\Exception\ChannelNotFoundException;
use App\Streaming\Domain\Repository\ChannelRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeleteChannelService
{
    public function __construct(
        private readonly ChannelRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(DeleteChannelRequest $request): DeleteChannelResponse
    {
        $channel = $this->repository->find($request->id);

        if (!$channel) {
            throw new ChannelNotFoundException($request->id);
        }

        $channelId = $channel->getId();

        $this->repository->delete($channel);

        $this->eventDispatcher->dispatch(
            new ChannelDeletedEvent($channelId),
            ChannelDeletedEvent::NAME
        );

        return new DeleteChannelResponse($channelId);
    }
}

