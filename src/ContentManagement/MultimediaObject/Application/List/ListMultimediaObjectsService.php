<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\List;

use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;

final class ListMultimediaObjectsService
{
    public function __construct(
        private readonly MultimediaObjectRepositoryInterface $repository
    ) {}

    public function __invoke(ListMultimediaObjectsRequest $request): ListMultimediaObjectsResponse
    {
        $sort = [$request->sort => $request->order];

        $multimediaObjects = $this->repository->findAll(
            $request->page,
            $request->limit,
            $sort,
            $request->filters
        );

        $total = $this->repository->countAll($request->filters);

        return new ListMultimediaObjectsResponse(
            multimediaObjects: $multimediaObjects,
            total: $total,
            page: $request->page,
            limit: $request->limit
        );
    }
}
