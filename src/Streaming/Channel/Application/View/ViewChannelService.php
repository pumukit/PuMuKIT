<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\View;

use App\Streaming\Channel\Domain\Exception\ChannelNotFoundException;
use App\Streaming\Channel\Domain\Repository\ChannelRepositoryInterface;

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
