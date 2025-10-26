<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\Create\CreateSeriesCommand;
use App\Series\Application\Create\CreateSeriesHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class CreateSeriesController extends AbstractController
{
    public function __construct(private CreateSeriesHandler $handler) {}

    public function __invoke(): RedirectResponse
    {
        $user = $this->getUser();

        $command = new CreateSeriesCommand($user->getId());

        $response = $this->handler->__invoke($command);

        return $this->redirectToRoute('series_view', ['id' => $response->id]);
    }
}
