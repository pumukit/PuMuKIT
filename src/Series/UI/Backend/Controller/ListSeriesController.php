<?php

namespace App\Series\UI\Backend\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ListSeriesController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@Series/UI/Backend/Pages/list.html.twig');
    }
}
