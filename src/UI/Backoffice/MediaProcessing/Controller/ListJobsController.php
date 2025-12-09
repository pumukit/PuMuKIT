<?php

declare(strict_types=1);

namespace App\UI\Backoffice\MediaProcessing\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ListJobsController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@MediaProcessing/Views/list.html.twig');
    }
}
