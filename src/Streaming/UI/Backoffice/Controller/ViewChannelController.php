<?php

declare(strict_types=1);

namespace App\Streaming\UI\Backoffice\Controller;

use App\Streaming\Application\Channel\View\ViewChannelRequest;
use App\Streaming\Application\Channel\View\ViewChannelService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ViewChannelController extends AbstractController
{
    public function __construct(private readonly ViewChannelService $service) {}

    public function __invoke(string $id): Response
    {
        $request = new ViewChannelRequest($id);
        $response = ($this->service)($request);

        return $this->render('@Streaming/UI/Backoffice/Views/view.html.twig', [
            'channel' => $response->channel,
        ]);
    }
}

