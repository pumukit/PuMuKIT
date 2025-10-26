<?php

namespace App\MultimediaObject\UI\Backend\Controller;

use App\MultimediaObject\Application\View\ViewMultimediaObjectHandler;
use App\MultimediaObject\Application\View\ViewMultimediaObjectQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ViewMultimediaObjectController extends AbstractController
{
    public function __construct(private ViewMultimediaObjectHandler $handler) {}

    public function __invoke(Request $request, string $id, string $tab = 'general'): Response
    {
        $response = $this->handler->handle(new ViewMultimediaObjectQuery($id, $tab));

        return $this->render('@MultimediaObject/UI/Backend/Pages/view.html.twig', [
            'object' => $response->object(),
            'tab' => $response->tab(),
        ]);
    }
}
