<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\View;

use App\Streaming\Domain\Exception\ChannelNotFoundException;
use App\Streaming\Domain\Repository\ChannelRepositoryInterface;

final class ViewChannelService
{
    public function __construct(private readonly ChannelRepositoryInterface $repository) {}

    public function __invoke(ViewChannelRequest $request): ViewChannelResponse
    {
        $channel = $this->repository->find($request->id);

        if (!$channel) {
            throw new ChannelNotFoundException($request->id);
        }

        return new ViewChannelResponse($channel);
    }
}

