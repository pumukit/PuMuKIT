<?php

declare(strict_types=1);

namespace App\Playlist\Infrastructure\Ui\Backoffice\Http\Controller;

use App\Playlist\Application\Delete\DeletePlaylistRequest;
use App\Playlist\Application\Delete\DeletePlaylistService;
use App\Shared\Domain\LoggerInterface;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class DeletePlaylistController extends AbstractController
{
    public function __construct(
        private readonly DeletePlaylistService $deletePlaylistService,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator
    ) {}

    public function __invoke(string $id): RedirectResponse
    {
        try {
            $requestDto = new DeletePlaylistRequest($id);

            $response = ($this->deletePlaylistService)($requestDto);

            if ($response->success) {
                $this->addFlash('success', $this->translator->trans($response->message, [], 'playlist'));
            } else {
                $this->addFlash('danger', $this->translator->trans($response->message, [], 'playlist'));
            }

            return $this->redirectToRoute('playlist_list');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getMessage(), [], 'playlist'));

            return $this->redirectToRoute('playlist_list');
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error deleting playlist', [
                'playlistId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', $this->translator->trans('playlist.error.unexpected_delete', [], 'playlist'));

            return $this->redirectToRoute('playlist_list');
        }
    }
}
