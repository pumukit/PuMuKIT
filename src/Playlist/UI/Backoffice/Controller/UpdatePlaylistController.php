<?php

declare(strict_types=1);

namespace App\Playlist\UI\Backoffice\Controller;

use App\Playlist\Application\Update\UpdatePlaylistService;
use App\Playlist\Domain\Exception\PlaylistNotFoundException;
use App\Playlist\UI\Backoffice\Event\PlaylistFormSubmitEvent;
use App\Playlist\UI\Backoffice\FormHandler\UpdatePlaylistFormHandler;
use App\Shared\Domain\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdatePlaylistController extends AbstractController
{
    public function __construct(
        private readonly UpdatePlaylistService $updatePlaylistService,
        private readonly UpdatePlaylistFormHandler $formHandler,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        try {
            $dto = $this->formHandler->handleRequest($request, $id);

            $response = ($this->updatePlaylistService)($dto);

            $submitEvent = new PlaylistFormSubmitEvent($response->playlist, $request->request->all());
            $this->eventDispatcher->dispatch($submitEvent, PlaylistFormSubmitEvent::NAME);

            if ($submitEvent->hasErrors()) {
                foreach ($submitEvent->getErrors() as $field => $errors) {
                    foreach ($errors as $error) {
                        $this->addFlash('danger', sprintf('%s: %s', $field, $error));
                    }
                }
            } else {
                $this->addFlash('success', sprintf('Playlist "%s" updated successfully', $response->playlist->getTitle()));
            }
        } catch (PlaylistNotFoundException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('playlist_list');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error updating playlist', [
                'playlistId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', 'Error updating the playlist');
        }

        return $this->redirectToRoute('playlist_view', ['id' => $id, 'tab' => 'edit']);
    }
}
