<?php

declare(strict_types=1);

namespace App\Playlist\UI\Backoffice\Controller;

use App\Playlist\Application\Delete\DeletePlaylistRequest;
use App\Playlist\Application\Delete\DeletePlaylistService;
use App\Shared\Domain\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class DeletePlaylistController extends AbstractController
{
    public function __construct(
        private readonly DeletePlaylistService $deletePlaylistService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(string $id): RedirectResponse
    {
        try {
            $requestDto = new DeletePlaylistRequest($id);

            $response = ($this->deletePlaylistService)($requestDto);

            if ($response->success) {
                $this->addFlash('success', $response->message);
            } else {
                $this->addFlash('danger', $response->message);
            }

            return $this->redirectToRoute('playlist_list');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('playlist_list');
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error deleting playlist', [
                'playlistId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', 'An error occurred while deleting the playlist');

            return $this->redirectToRoute('playlist_list');
        }
    }
}
