<?php

declare(strict_types=1);

namespace App\Streaming\UI\Backoffice\Controller;

use App\Streaming\Application\Channel\Create\CreateChannelRequest;
use App\Streaming\Application\Channel\Create\CreateChannelService;
use App\Shared\Domain\TranslatorInterface;
use Pumukit\SchemaBundle\Document\Live;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreateChannelController extends AbstractController
{
    public function __construct(
        private readonly CreateChannelService $service,
        private readonly TranslatorInterface $translator
    ) {}

    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $requestDto = new CreateChannelRequest(
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

            $response = ($this->service)($requestDto);

            $this->addFlash('success', $this->translator->trans('streaming.flash.channel_created', [], 'streaming'));

            return $this->redirectToRoute('streaming_channel_view', ['id' => $response->channel->getId()]);
        }

        return $this->render('@Streaming/UI/Backoffice/Views/create.html.twig');
    }
}

