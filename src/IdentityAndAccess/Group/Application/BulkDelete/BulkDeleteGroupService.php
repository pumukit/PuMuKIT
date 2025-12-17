<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\BulkDelete;

use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use App\Shared\Domain\LoggerInterface;

final class BulkDeleteGroupService
{
    public function __construct(
        private readonly GroupRepositoryInterface $repository,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(BulkDeleteGroupRequest $request): BulkDeleteGroupResponse
    {
        BulkDeleteGroupValidator::validate($request);

        $deletedCount = 0;
        $failedIds = [];
        $errors = [];

        foreach ($request->ids as $id) {
            try {
                $group = $this->repository->find($id);

                if (!$group) {
                    $failedIds[] = $id;
                    $errors[] = [
                        'id' => $id,
                        'message' => 'Group not found',
                    ];

                    continue;
                }

                $this->repository->delete($group);
                ++$deletedCount;
            } catch (\Exception $e) {
                $failedIds[] = $id;
                $errors[] = [
                    'id' => $id,
                    'message' => $e->getMessage(),
                ];

                $this->logger->error('Error deleting group in bulk operation', [
                    'groupId' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return new BulkDeleteGroupResponse(
            deletedCount: $deletedCount,
            failedIds: $failedIds,
            errors: $errors
        );
    }
}
