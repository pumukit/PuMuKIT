<?php

declare(strict_types=1);

namespace App\Playlist\Application\BulkDelete;

use App\Playlist\Application\Delete\DeletePlaylistRequest;
use App\Playlist\Application\Delete\DeletePlaylistService;
use App\Playlist\Domain\Exception\PlaylistNotFoundException;
use App\Shared\Domain\LoggerInterface;

final class BulkDeletePlaylistService
{
    public function __construct(
        private DeletePlaylistService $deletePlaylistService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(BulkDeletePlaylistRequest $request): BulkDeletePlaylistResponse
    {
        $deletedCount = 0;
        $failedIds = [];
        $errors = [];

        foreach ($request->playlistIds as $playlistId) {
            try {
                $deleteRequest = new DeletePlaylistRequest($playlistId);
                $deleteResponse = ($this->deletePlaylistService)($deleteRequest);

                if (!$deleteResponse->success) {
                    $failedIds[] = $playlistId;
                    $errors[$playlistId] = $deleteResponse->message;
                    $this->logger->warning('Failed to delete playlist', [
                        'id' => $playlistId,
                        'reason' => $deleteResponse->message,
                    ]);

                    continue;
                }

                ++$deletedCount;

                $this->logger->info('Playlist deleted successfully', [
                    'id' => $playlistId,
                    'message' => $deleteResponse->message,
                ]);
            } catch (PlaylistNotFoundException $e) {
                $failedIds[] = $playlistId;
                $errors[$playlistId] = 'Playlist not found';
                $this->logger->warning('Playlist not found for deletion', ['id' => $playlistId]);
            } catch (\Exception $e) {
                $failedIds[] = $playlistId;
                $errors[$playlistId] = $e->getMessage();
                $this->logger->error('Error deleting playlist', [
                    'id' => $playlistId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return new BulkDeletePlaylistResponse(
            deletedCount: $deletedCount,
            failedIds: $failedIds,
            errors: $errors
        );
    }
}
