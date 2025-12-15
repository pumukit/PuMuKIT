<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Infrastructure\Ui\Backoffice\Http\Controller;

use App\ContentManagement\Person\Application\ViewPerson\ViewPersonRequest;
use App\ContentManagement\Person\Application\ViewPerson\ViewPersonService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ViewPersonController extends AbstractController
{
    public function __construct(
        private ViewPersonService $viewPersonService
    ) {}

    public function __invoke(string $id): Response
    {
        $viewPersonRequest = new ViewPersonRequest($id);
        $personResponse = ($this->viewPersonService)($viewPersonRequest);
        $person = $personResponse->person;

        return $this->render('@Person/Views/view.html.twig', [
            'person' => $person,
        ]);
    }
}
