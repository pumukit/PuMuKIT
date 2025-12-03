<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\Update;

use App\Streaming\Domain\Event\ChannelUpdatedEvent;
use App\Streaming\Domain\Exception\ChannelNotFoundException;
use App\Streaming\Domain\Repository\ChannelRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final class UpdateChannelService
{
    public function __construct(
        private readonly ChannelRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(UpdateChannelRequest $request): UpdateChannelResponse
    {
        $channel = $this->repository->find($request->id);

        if (!$channel) {
            throw new ChannelNotFoundException($request->id);
        }

        $channel->setName($request->name);
        $channel->setDescription($request->description);
        $channel->setUrl($request->url);
        $channel->setSourceName($request->sourceName);

        if ($request->passwd !== null) {
            $channel->setPasswd($request->passwd);
        }

        if ($request->liveType) {
            $channel->setLiveType($request->liveType);
        }

        if ($request->ipSource !== null) {
            $channel->setIpSource($request->ipSource);
        }

        $channel->setIndexPlay($request->indexPlay);
        $channel->setBroadcasting($request->broadcasting);
        $channel->setDebug($request->debug);
        $channel->setChat($request->chat);

        $this->repository->save($channel);

        $this->eventBus->dispatch(
            new ChannelUpdatedEvent($channel),
            ChannelUpdatedEvent::NAME
        );

        return new UpdateChannelResponse($channel);
    }
}

