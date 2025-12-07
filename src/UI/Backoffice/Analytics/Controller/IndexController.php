<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Analytics\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class IndexController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@Analytics/Views//index.html.twig');
    }
}
