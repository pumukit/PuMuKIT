<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Person\Controller;

use App\ContentManagement\Person\Application\UpdatePerson\UpdatePersonRequest;
use App\ContentManagement\Person\Application\UpdatePerson\UpdatePersonService;
use App\ContentManagement\Person\Application\ViewPerson\ViewPersonRequest;
use App\ContentManagement\Person\Application\ViewPerson\ViewPersonService;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdatePersonController extends AbstractController
{
    public function __construct(
        private ViewPersonService $viewPersonService,
        private UpdatePersonService $updatePersonService,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(string $id, Request $request): Response
    {
        $viewPersonRequest = new ViewPersonRequest($id);
        $personResponse = ($this->viewPersonService)($viewPersonRequest);
        $person = $personResponse->person;
        if ($request->isMethod('POST')) {
            try {
                $honorific = json_decode((string) $request->request->get('honorific', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $firm = json_decode((string) $request->request->get('firm', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $post = json_decode((string) $request->request->get('post', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $bio = json_decode((string) $request->request->get('bio', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $updatePersonRequest = new UpdatePersonRequest(
                    id: $id,
                    name: $request->request->get('name'),
                    email: $request->request->get('email') ?: null,
                    web: $request->request->get('web') ?: null,
                    phone: $request->request->get('phone') ?: null,
                    honorific: $honorific,
                    firm: $firm,
                    post: $post,
                    bio: $bio
                );
                ($this->updatePersonService)($updatePersonRequest);
                $this->addFlash('success', $this->translator->trans(
                    'person.flash.updated',
                    ['%name%' => $person->getName()],
                    'person'
                ));

                return $this->redirectToRoute('person_index');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans(
                    'person.error.update_failed',
                    ['%message%' => $e->getMessage()],
                    'person'
                ));
            }
        }

        return $this->render('@Person/Views/update.html.twig', [
            'person' => $person,
        ]);
    }
}
