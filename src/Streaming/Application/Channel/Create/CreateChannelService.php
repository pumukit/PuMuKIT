<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\Create;

use App\Streaming\Domain\Event\ChannelCreatedEvent;
use App\Streaming\Domain\Repository\ChannelRepositoryInterface;
use Pumukit\SchemaBundle\Document\Live;
use App\Shared\Domain\EventBusInterface;

final class CreateChannelService
{
    public function __construct(
        private readonly ChannelRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(CreateChannelRequest $request): CreateChannelResponse
    {
        $channel = new Live();
        $channel->setName($request->name);
        $channel->setDescription($request->description);
        $channel->setUrl($request->url);
        $channel->setSourceName($request->sourceName);

        if ($request->passwd) {
            $channel->setPasswd($request->passwd);
        }

        if ($request->liveType) {
            $channel->setLiveType($request->liveType);
        }

        if ($request->ipSource) {
            $channel->setIpSource($request->ipSource);
        }

        $channel->setIndexPlay($request->indexPlay);
        $channel->setBroadcasting($request->broadcasting);
        $channel->setDebug($request->debug);
        $channel->setChat($request->chat);

        $this->repository->save($channel);

        $this->eventBus->dispatch(
            new ChannelCreatedEvent($channel),
            ChannelCreatedEvent::NAME
        );

        return new CreateChannelResponse($channel);
    }
}

