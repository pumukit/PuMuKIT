<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Role\Controller;

use App\ContentManagement\Role\Application\CreateRole\CreateRoleRequest;
use App\ContentManagement\Role\Application\CreateRole\CreateRoleService;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreateRoleController extends AbstractController
{
    public function __construct(
        private CreateRoleService $createRoleService,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $name = json_decode((string) $request->request->get('name', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $text = json_decode((string) $request->request->get('text', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];

                $createRoleRequest = new CreateRoleRequest(
                    cod: $request->request->get('cod'),
                    name: $name,
                    text: $text,
                    xml: $request->request->get('xml') ?: null,
                    display: $request->request->getBoolean('display'),
                    readOnly: $request->request->getBoolean('readOnly')
                );

                ($this->createRoleService)($createRoleRequest);

                $this->addFlash('success', $this->translator->trans(
                    'role.flash.created',
                    ['%cod%' => $request->request->get('cod')],
                    'role'
                ));

                return $this->redirectToRoute('role_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans(
                    'role.error.create_failed',
                    ['%message%' => $e->getMessage()],
                    'role'
                ));
            }
        }

        return $this->render('@Role/Views/create.html.twig');
    }
}
