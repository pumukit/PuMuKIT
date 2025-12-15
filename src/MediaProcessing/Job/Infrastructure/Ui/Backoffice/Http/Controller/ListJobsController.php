<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Infrastructure\Ui\Backoffice\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ListJobsController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@Job/Views/list.html.twig');
    }
}
