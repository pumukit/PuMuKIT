<?php

namespace App\MultimediaObject\UI\Backend\Controller;

use App\MultimediaObject\Application\ViewMultimediaObject\ViewMultimediaObjectHandler;
use App\MultimediaObject\Application\ViewMultimediaObject\ViewMultimediaObjectQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ViewMultimediaObjectController extends AbstractController
{
    public function __construct(private ViewMultimediaObjectHandler $handler) {}

    public function __invoke(string $id): Response
    {
        $response = $this->handler->handle(new ViewMultimediaObjectQuery($id));

        return $this->render('@MultimediaObject/UI/Backend/Pages/view.html.twig', [
            'object' => $response->object(),
        ]);
    }
}
