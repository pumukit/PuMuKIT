<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Streaming\Channel\Controller;

use App\Shared\Domain\TranslatorInterface;
use App\Streaming\Channel\Application\Update\UpdateChannelRequest;
use App\Streaming\Channel\Application\Update\UpdateChannelService;
use App\Streaming\Channel\Application\View\ViewChannelRequest;
use App\Streaming\Channel\Application\View\ViewChannelService;
use Pumukit\SchemaBundle\Document\Live;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateChannelController extends AbstractController
{
    public function __construct(
        private readonly ViewChannelService $viewService,
        private readonly UpdateChannelService $updateService,
        private readonly TranslatorInterface $translator
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $viewRequest = new ViewChannelRequest($id);
        $viewResponse = ($this->viewService)($viewRequest);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $updateRequest = new UpdateChannelRequest(
                id: $id,
                name: $data['name'] ?? ['en' => ''],
                description: $data['description'] ?? ['en' => ''],
                url: $data['url'] ?? '',
                sourceName: $data['source_name'] ?? '',
                passwd: $data['passwd'] ?? null,
                liveType: $data['live_type'] ?? Live::LIVE_TYPE_WOWZA,
                ipSource: $data['ip_source'] ?? null,
                indexPlay: isset($data['index_play']),
                broadcasting: isset($data['broadcasting']),
                debug: isset($data['debug']),
                chat: isset($data['chat'])
            );

            ($this->updateService)($updateRequest);

            $this->addFlash('success', $this->translator->trans('streaming.flash.channel_updated', [], 'streaming'));

            return $this->redirectToRoute('streaming_channel_view', ['id' => $id]);
        }

        return $this->render('@Streaming/Views/edit.html.twig', [
            'channel' => $viewResponse->channel,
        ]);
    }
}
