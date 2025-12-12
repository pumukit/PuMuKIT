<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Role\Controller;

use App\ContentManagement\Role\Application\UpdateRole\UpdateRoleRequest;
use App\ContentManagement\Role\Application\UpdateRole\UpdateRoleService;
use App\ContentManagement\Role\Application\ViewRole\ViewRoleRequest;
use App\ContentManagement\Role\Application\ViewRole\ViewRoleService;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateRoleController extends AbstractController
{
    public function __construct(
        private ViewRoleService $viewRoleService,
        private UpdateRoleService $updateRoleService,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(string $id, Request $request): Response
    {
        $viewRoleRequest = new ViewRoleRequest($id);
        $roleResponse = ($this->viewRoleService)($viewRoleRequest);
        $role = $roleResponse->role;

        if ($request->isMethod('POST')) {
            try {
                $name = json_decode($request->request->get('name', '{}'), true) ?: [];
                $text = json_decode($request->request->get('text', '{}'), true) ?: [];

                $updateRoleRequest = new UpdateRoleRequest(
                    id: $id,
                    name: $name,
                    text: $text,
                    xml: $request->request->get('xml') ?: null,
                    display: $request->request->getBoolean('display')
                );

                ($this->updateRoleService)($updateRoleRequest);

                $this->addFlash('success', $this->translator->trans(
                    'role.flash.updated',
                    ['%cod%' => $role->getCod()],
                    'role'
                ));

                return $this->redirectToRoute('role_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans(
                    'role.error.update_failed',
                    ['%message%' => $e->getMessage()],
                    'role'
                ));
            }
        }

        return $this->render('@Role/Views/update.html.twig', [
            'role' => $role,
        ]);
    }
}
