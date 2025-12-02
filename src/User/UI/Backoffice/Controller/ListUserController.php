<?php

namespace App\User\UI\Backoffice\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ListUserController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@User/UI/Backoffice/Views/list.html.twig');
    }
}
