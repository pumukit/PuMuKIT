<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LogoutController extends AbstractController
{
    /**
     * @Route("/logout", name="pumukit_logout")
     */
    public function logout(): Response
    {
        return $this->redirectToRoute('homepage');
    }
}
