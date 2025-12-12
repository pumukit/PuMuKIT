<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Playlist\Controller;

use Pumukit\SchemaBundle\Document\User;
use App\ContentManagement\Playlist\Application\Create\CreatePlaylistRequest;
use App\ContentManagement\Playlist\Application\Create\CreatePlaylistService;
use App\Shared\Domain\LoggerInterface;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class CreatePlaylistController extends AbstractController
{
    public function __construct(
        private readonly CreatePlaylistService $createPlaylistService,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator
    ) {}

    public function __invoke(): RedirectResponse
    {
        try {
            $user = $this->getUser();

            if (!$user instanceof User) {
                throw new \RuntimeException('Invalid user type');
            }

            $request = new CreatePlaylistRequest($user->getId());

            $response = ($this->createPlaylistService)($request);

            $this->addFlash('success', $this->translator->trans(
                'playlist.flash.created',
                ['%title%' => $response->playlist->getTitle()],
                'playlist'
            ));

            return $this->redirectToRoute('playlist_view', ['id' => $response->playlist->getId()]);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getMessage(), [], 'playlist'));

            return $this->redirectToRoute('playlist_list');
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error creating playlist', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', $this->translator->trans('playlist.error.unexpected', [], 'playlist'));

            return $this->redirectToRoute('playlist_list');
        }
    }
}
