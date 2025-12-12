<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\BulkToggleAnnounce;

use App\ContentManagement\MultimediaObject\Application\Find\FindMultimediaObjectRequest;
use App\ContentManagement\MultimediaObject\Application\Find\FindMultimediaObjectService;
use App\ContentManagement\MultimediaObject\Domain\Event\MultimediaObjectUpdatedEvent;
use App\ContentManagement\MultimediaObject\Domain\Exception\MultimediaObjectNotFoundException;
use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\Shared\Domain\EventBusInterface;
use Psr\Log\LoggerInterface;

final class BulkToggleAnnounceMultimediaObjectService
{
    public function __construct(
        private FindMultimediaObjectService $findMultimediaObjectService,
        private MultimediaObjectRepositoryInterface $multimediaObjectRepository,
        private EventBusInterface $eventBus,
        private LoggerInterface $logger
    ) {}

    public function __invoke(BulkToggleAnnounceMultimediaObjectRequest $request): BulkToggleAnnounceMultimediaObjectResponse
    {
        BulkToggleAnnounceMultimediaObjectValidator::validate($request);

        $updatedCount = 0;
        $announcedCount = 0;
        $unAnnouncedCount = 0;
        $failedIds = [];
        $errors = [];

        foreach ($request->multimediaObjectIds as $id) {
            try {
                $findRequest = new FindMultimediaObjectRequest($id);
                $findResponse = ($this->findMultimediaObjectService)($findRequest);
                $multimediaObject = $findResponse->multimediaObject;

                $this->multimediaObjectRepository->save($multimediaObject);

                $this->eventBus->dispatch(new MultimediaObjectUpdatedEvent($multimediaObject));

                ++$updatedCount;

                $this->logger->info('Multimedia object announce toggled successfully', [
                    'id' => $id,
                    'title' => $multimediaObject->getTitle(),
                    'status' => $multimediaObject->getStatus(),
                    'was_published' => true,
                ]);
            } catch (MultimediaObjectNotFoundException $e) {
                $failedIds[] = $id;
                $errors[$id] = 'Multimedia object not found';
                $this->logger->warning('Multimedia object not found for toggle announce', ['id' => $id]);
            } catch (\Exception $e) {
                $failedIds[] = $id;
                $errors[$id] = $e->getMessage();
                $this->logger->error('Error toggling announce for multimedia object', [
                    'id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return new BulkToggleAnnounceMultimediaObjectResponse(
            updatedCount: $updatedCount,
            announcedCount: $announcedCount,
            unAnnouncedCount: $unAnnouncedCount,
            failedIds: $failedIds,
            errors: $errors
        );
    }
}
