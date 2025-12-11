<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Streaming\Channel\Controller;

use App\Shared\Domain\TranslatorInterface;
use App\Streaming\Channel\Application\Delete\DeleteChannelRequest;
use App\Streaming\Channel\Application\Delete\DeleteChannelService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DeleteChannelController extends AbstractController
{
    public function __construct(
        private readonly DeleteChannelService $service,
        private readonly TranslatorInterface $translator
    ) {}

    public function __invoke(string $id): Response
    {
        $request = new DeleteChannelRequest($id);
        ($this->service)($request);

        $this->addFlash('success', $this->translator->trans('streaming.flash.channel_deleted', [], 'streaming'));

        return $this->redirectToRoute('streaming_channels_list');
    }
}
