<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\Search;

use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filter;
use App\Shared\Domain\Criteria\Order;

final class SearchUsersByCriteriaService
{
    public function __construct(
        private readonly UserRepositoryInterface $repository
    ) {}

    public function __invoke(SearchUsersByCriteriaRequest $request): array
    {
        $filters = array_map(fn ($f) => Filter::fromValues($f), $request->filters);

        $order = $request->orderBy
            ? new Order($request->orderBy, $request->order ?? 'asc')
            : null;

        $criteria = new Criteria(
            $filters,
            $order,
            $request->offset,
            $request->limit
        );

        return [
            'items' => $this->repository->matching($criteria),
            'total' => $this->repository->totalMatching($criteria),
        ];
    }
}
