<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\BulkDelete;

use App\Shared\Domain\LoggerInterface;
use App\Streaming\Application\Channel\Delete\DeleteChannelRequest;
use App\Streaming\Application\Channel\Delete\DeleteChannelService;
use App\Streaming\Domain\Exception\ChannelNotFoundException;

final class BulkDeleteChannelsService
{
    public function __construct(
        private readonly DeleteChannelService $deleteChannelService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(BulkDeleteChannelsRequest $request): BulkDeleteChannelsResponse
    {
        $deletedCount = 0;
        $failedIds = [];
        $errors = [];

        foreach ($request->channelIds as $channelId) {
            try {
                $deleteRequest = new DeleteChannelRequest($channelId);
                ($this->deleteChannelService)($deleteRequest);

                ++$deletedCount;

                $this->logger->info('Canal eliminado correctamente', [
                    'id' => $channelId,
                ]);
            } catch (ChannelNotFoundException $e) {
                $failedIds[] = $channelId;
                $errors[$channelId] = 'Canal no encontrado';
                $this->logger->warning('Canal no encontrado para eliminación', ['id' => $channelId]);
            } catch (\Exception $e) {
                $failedIds[] = $channelId;
                $errors[$channelId] = $e->getMessage();
                $this->logger->error('Error eliminando canal', [
                    'id' => $channelId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return new BulkDeleteChannelsResponse(
            deletedCount: $deletedCount,
            failedIds: $failedIds,
            errors: $errors
        );
    }
}

