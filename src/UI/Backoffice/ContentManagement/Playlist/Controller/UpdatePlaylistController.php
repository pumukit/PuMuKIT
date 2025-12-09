<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Playlist\Controller;

use App\ContentManagement\Playlist\Application\Update\UpdatePlaylistService;
use App\ContentManagement\Playlist\Domain\Exception\PlaylistNotFoundException;
use App\Shared\Domain\LoggerInterface;
use App\Shared\Domain\TranslatorInterface;
use App\UI\Backoffice\ContentManagement\Playlist\Event\PlaylistFormSubmitEvent;
use App\UI\Backoffice\ContentManagement\Playlist\FormHandler\UpdatePlaylistFormHandler;
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
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator
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
                        $this->addFlash('danger', $this->translator->trans(
                            'playlist.form.error',
                            ['%field%' => $field, '%error%' => $error],
                            'playlist'
                        ));
                    }
                }
            } else {
                $this->addFlash('success', $this->translator->trans(
                    'playlist.flash.updated',
                    ['%title%' => $response->playlist->getTitle()],
                    'playlist'
                ));
            }
        } catch (PlaylistNotFoundException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getMessage(), [], 'playlist'));

            return $this->redirectToRoute('playlist_list');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getMessage(), [], 'playlist'));
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error updating playlist', [
                'playlistId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', $this->translator->trans('playlist.error.unexpected_update', [], 'playlist'));
        }

        return $this->redirectToRoute('playlist_view', ['id' => $id, 'tab' => 'edit']);
    }
}
