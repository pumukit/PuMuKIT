<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\BulkDelete;

use App\MultimediaObject\Domain\Exception\MultimediaObjectNotFoundException;
use App\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\Shared\Domain\LoggerInterface;

final class BulkDeleteMultimediaObjectsService
{
    public function __construct(
        private readonly MultimediaObjectRepositoryInterface $repository,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(BulkDeleteMultimediaObjectsRequest $request): BulkDeleteMultimediaObjectsResponse
    {
        $deletedCount = 0;
        $failedIds = [];
        $errors = [];

        foreach ($request->multimediaObjectIds as $id) {
            try {
                $multimediaObject = $this->repository->find($id);

                if (!$multimediaObject) {
                    $failedIds[] = $id;
                    $errors[$id] = 'MultimediaObject not found';
                    $this->logger->warning('MultimediaObject not found for deletion', ['id' => $id]);
                    continue;
                }

                $this->repository->delete($multimediaObject);
                ++$deletedCount;

                $this->logger->info('MultimediaObject deleted successfully', ['id' => $id]);
            } catch (\Exception $e) {
                $failedIds[] = $id;
                $errors[$id] = $e->getMessage();
                $this->logger->error('Error deleting multimedia object', [
                    'id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return new BulkDeleteMultimediaObjectsResponse(
            deletedCount: $deletedCount,
            failedIds: $failedIds,
            errors: $errors
        );
    }
}

