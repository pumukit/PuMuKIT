<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Person\Controller;

use App\ContentManagement\Person\Application\CreatePerson\CreatePersonRequest;
use App\ContentManagement\Person\Application\CreatePerson\CreatePersonService;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreatePersonController extends AbstractController
{
    public function __construct(
        private CreatePersonService $createPersonService,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $honorific = json_decode((string) $request->request->get('honorific', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $firm = json_decode((string) $request->request->get('firm', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $post = json_decode((string) $request->request->get('post', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $bio = json_decode((string) $request->request->get('bio', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $createPersonRequest = new CreatePersonRequest(
                    name: $request->request->get('name'),
                    email: $request->request->get('email') ?: null,
                    web: $request->request->get('web') ?: null,
                    phone: $request->request->get('phone') ?: null,
                    honorific: $honorific,
                    firm: $firm,
                    post: $post,
                    bio: $bio
                );
                ($this->createPersonService)($createPersonRequest);
                $this->addFlash('success', $this->translator->trans(
                    'person.flash.created',
                    ['%name%' => $request->request->get('name')],
                    'person'
                ));

                return $this->redirectToRoute('person_index');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans(
                    'person.error.create_failed',
                    ['%message%' => $e->getMessage()],
                    'person'
                ));
            }
        }

        return $this->render('@Person/Views/create.html.twig');
    }
}
