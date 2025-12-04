<?php

declare(strict_types=1);

namespace App\Transcoding\UI\Backoffice\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ListJobsController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@Transcoding/UI/Backoffice/Views/list.html.twig');
    }
}
