<?php

namespace App\Playlist\UI\Backoffice\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@Playlist/UI/Backoffice/Views/index.html.twig');
    }
}
