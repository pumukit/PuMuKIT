<?php

declare(strict_types=1);

namespace App\Playlist\UI\Backoffice\Controller;

use App\Playlist\Application\Create\CreatePlaylistRequest;
use App\Playlist\Application\Create\CreatePlaylistService;
use App\Shared\Domain\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class CreatePlaylistController extends AbstractController
{
    public function __construct(
        private readonly CreatePlaylistService $createPlaylistService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(): RedirectResponse
    {
        try {
            $user = $this->getUser();

            $request = new CreatePlaylistRequest($user->getId());

            $response = ($this->createPlaylistService)($request);

            $this->addFlash('success', sprintf(
                'Playlist "%s" created successfully',
                $response->playlist->getTitle()
            ));

            return $this->redirectToRoute('playlist_view', ['id' => $response->playlist->getId()]);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('playlist_list');
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error creating playlist', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', 'An error occurred while creating the playlist');

            return $this->redirectToRoute('playlist_list');
        }
    }
}
