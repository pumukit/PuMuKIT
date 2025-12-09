<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Streaming\Controller;

use App\Streaming\Channel\Application\View\ViewChannelRequest;
use App\Streaming\Channel\Application\View\ViewChannelService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ViewChannelController extends AbstractController
{
    public function __construct(private readonly ViewChannelService $service) {}

    public function __invoke(string $id): Response
    {
        $request = new ViewChannelRequest($id);
        $response = ($this->service)($request);

        return $this->render('@Streaming/Views/view.html.twig', [
            'channel' => $response->channel,
        ]);
    }
}
