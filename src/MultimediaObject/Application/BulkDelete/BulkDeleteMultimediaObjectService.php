<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\BulkDelete;

use App\MultimediaObject\Application\Delete\DeleteMultimediaObjectRequest;
use App\MultimediaObject\Application\Delete\DeleteMultimediaObjectService;
use App\MultimediaObject\Application\Find\FindMultimediaObjectRequest;
use App\MultimediaObject\Application\Find\FindMultimediaObjectService;
use App\MultimediaObject\Domain\Exception\MultimediaObjectNotFoundException;
use Psr\Log\LoggerInterface;

final class BulkDeleteMultimediaObjectService
{
    public function __construct(
        private FindMultimediaObjectService $findMultimediaObjectService,
        private DeleteMultimediaObjectService $deleteMultimediaObjectService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(BulkDeleteMultimediaObjectRequest $request): BulkDeleteMultimediaObjectResponse
    {
        BulkDeleteMultimediaObjectValidator::validate($request);

        $deletedCount = 0;
        $failedIds = [];
        $errors = [];

        foreach ($request->multimediaObjectIds as $id) {
            try {
                $findRequest = new FindMultimediaObjectRequest($id);
                $findResponse = ($this->findMultimediaObjectService)($findRequest);
                $multimediaObject = $findResponse->multimediaObject;

                $deleteRequest = new DeleteMultimediaObjectRequest($id);
                ($this->deleteMultimediaObjectService)($deleteRequest);

                $deletedCount++;

                $this->logger->info('Multimedia object deleted successfully', [
                    'id' => $id,
                    'title' => $multimediaObject->getTitle()
                ]);

            } catch (MultimediaObjectNotFoundException $e) {
                $failedIds[] = $id;
                $errors[$id] = 'Multimedia object not found';
                $this->logger->warning('Multimedia object not found for deletion', ['id' => $id]);
            } catch (\Exception $e) {
                $failedIds[] = $id;
                $errors[$id] = $e->getMessage();
                $this->logger->error('Error deleting multimedia object', [
                    'id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return new BulkDeleteMultimediaObjectResponse(
            deletedCount: $deletedCount,
            failedIds: $failedIds,
            errors: $errors
        );
    }
}

