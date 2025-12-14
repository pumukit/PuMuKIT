<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\Update;

use Pumukit\SchemaBundle\Document\Live;
use App\Shared\Domain\EventBusInterface;
use App\Streaming\Channel\Domain\Event\ChannelUpdatedEvent;
use App\Streaming\Channel\Domain\Exception\ChannelNotFoundException;
use App\Streaming\Channel\Domain\Repository\ChannelRepositoryInterface;

final class UpdateChannelService
{
    public function __construct(
        private readonly ChannelRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(UpdateChannelRequest $request): UpdateChannelResponse
    {
        UpdateChannelValidator::validate($request);

        $channel = $this->repository->find($request->id);

        if (!$channel instanceof Live) {
            throw new ChannelNotFoundException($request->id);
        }

        $channel->setName($request->name);
        $channel->setDescription($request->description);
        $channel->setUrl($request->url);
        $channel->setSourceName($request->sourceName);

        if (null !== $request->passwd) {
            $channel->setPasswd($request->passwd);
        }

        if ($request->liveType) {
            $channel->setLiveType($request->liveType);
        }

        if (null !== $request->ipSource) {
            $channel->setIpSource($request->ipSource);
        }

        $channel->setIndexPlay($request->indexPlay);
        $channel->setBroadcasting($request->broadcasting);
        $channel->setDebug($request->debug);
        $channel->setChat($request->chat);

        $this->repository->save($channel);

        $this->eventBus->dispatch(new ChannelUpdatedEvent($channel));

        return new UpdateChannelResponse($channel);
    }
}
