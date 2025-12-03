<?php

namespace App\Playlist\UI\Backoffice\Controller;

use App\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use App\Playlist\UI\Backoffice\Event\PlaylistFormBuildEvent;
use App\Playlist\UI\Backoffice\Event\PlaylistViewTabsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ViewPlaylistController extends AbstractController
{
    public function __construct(
        private PlaylistRepositoryInterface $playlistRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(Request $request, string $id, string $tab = 'general'): Response
    {
        $playlist = $this->playlistRepository->find($id);

        if (!$playlist) {
            $this->addFlash('danger', 'Playlist not found');

            return $this->redirectToRoute('playlist_list');
        }

        $viewTabsEvent = new PlaylistViewTabsEvent($playlist);
        $this->eventDispatcher->dispatch($viewTabsEvent, PlaylistViewTabsEvent::NAME);

        $allTabKeys = array_map(fn ($t) => $t['key'], $viewTabsEvent->getTabs());

        if (!in_array($tab, $allTabKeys)) {
            return $this->redirectToRoute('playlist_view', [
                'id' => $id,
                'tab' => 'general',
            ]);
        }

        $formBuildEvent = null;
        if ('edit' === $tab) {
            $formBuildEvent = new PlaylistFormBuildEvent($playlist);
            $this->eventDispatcher->dispatch($formBuildEvent, PlaylistFormBuildEvent::NAME);
        }

        return $this->render('@Playlist/UI/Backoffice/Views/view.html.twig', [
            'playlist' => $playlist,
            'tab' => $tab,
            'formBuildEvent' => $formBuildEvent,
            'viewTabsEvent' => $viewTabsEvent,
            'customTabs' => $viewTabsEvent->getTabs(),
        ]);
    }
}
