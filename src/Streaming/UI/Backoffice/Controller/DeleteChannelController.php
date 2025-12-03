<?php

declare(strict_types=1);

namespace App\Streaming\UI\Backoffice\Controller;

use App\Streaming\Application\Channel\Delete\DeleteChannelRequest;
use App\Streaming\Application\Channel\Delete\DeleteChannelService;
use App\Shared\Domain\TranslatorInterface;
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

