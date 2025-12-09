<?php

namespace App\UI\Backoffice\ContentManagement\Playlist\Controller;

use App\ContentManagement\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use App\Shared\Domain\TranslatorInterface;
use App\UI\Backoffice\ContentManagement\Playlist\Event\PlaylistFormBuildEvent;
use App\UI\Backoffice\ContentManagement\Playlist\Event\PlaylistViewTabsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ViewPlaylistController extends AbstractController
{
    public function __construct(
        private PlaylistRepositoryInterface $playlistRepository,
        private EventDispatcherInterface $eventDispatcher,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(Request $request, string $id, string $tab = 'general'): Response
    {
        $playlist = $this->playlistRepository->find($id);

        if (!$playlist) {
            $this->addFlash('danger', $this->translator->trans('playlist.error.not_found', [], 'playlist'));

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

        return $this->render('@Playlist/Views/view.html.twig', [
            'playlist' => $playlist,
            'tab' => $tab,
            'formBuildEvent' => $formBuildEvent,
            'viewTabsEvent' => $viewTabsEvent,
            'customTabs' => $viewTabsEvent->getTabs(),
        ]);
    }
}
