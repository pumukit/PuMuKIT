<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\List;

use App\Streaming\Channel\Domain\Repository\ChannelRepositoryInterface;

final class ListChannelsService
{
    public function __construct(private readonly ChannelRepositoryInterface $repository) {}

    public function __invoke(ListChannelsRequest $request): ListChannelsResponse
    {
        $sort = [$request->sort => $request->order];

        $channels = $this->repository->findAll($request->page, $request->limit, $sort);
        $total = $this->repository->countAll();

        return new ListChannelsResponse(
            channels: $channels,
            total: $total,
            page: $request->page,
            limit: $request->limit
        );
    }
}
