<?php

declare(strict_types=1);

namespace App\Transcoding\UI\Backoffice\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ListJobsController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@Transcoding/UI/Backoffice/Views/list.html.twig');
    }
}
