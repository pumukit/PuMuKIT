<?php

declare(strict_types=1);

namespace App\Transcoding\Application\Job\List;

use App\Transcoding\Domain\Repository\JobRepositoryInterface;

final class ListJobsService
{
    public function __construct(private readonly JobRepositoryInterface $repository) {}

    public function __invoke(ListJobsRequest $request): ListJobsResponse
    {
        $sort = [$request->sort => $request->order];

        $jobs = $this->repository->findAll($request->page, $request->limit, $sort);
        $total = $this->repository->countAll();

        return new ListJobsResponse(
            jobs: $jobs,
            total: $total,
            page: $request->page,
            limit: $request->limit
        );
    }
}

