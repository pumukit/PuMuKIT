<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Infrastructure\Ui\Backoffice\Http\Controller;

use App\ContentManagement\Person\Application\DeletePerson\DeletePersonRequest;
use App\ContentManagement\Person\Application\DeletePerson\DeletePersonService;
use App\ContentManagement\Person\Application\ViewPerson\ViewPersonRequest;
use App\ContentManagement\Person\Application\ViewPerson\ViewPersonService;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DeletePersonController extends AbstractController
{
    public function __construct(
        private ViewPersonService $viewPersonService,
        private DeletePersonService $deletePersonService,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(string $id): Response
    {
        try {
            $viewPersonRequest = new ViewPersonRequest($id);
            $personResponse = ($this->viewPersonService)($viewPersonRequest);
            $name = $personResponse->person->getName();

            $deletePersonRequest = new DeletePersonRequest($id);
            ($this->deletePersonService)($deletePersonRequest);

            $this->addFlash('success', $this->translator->trans(
                'person.flash.deleted',
                ['%name%' => $name],
                'person'
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans(
                'person.error.delete_failed',
                ['%message%' => $e->getMessage()],
                'person'
            ));
        }

        return $this->redirectToRoute('person_list');
    }
}
