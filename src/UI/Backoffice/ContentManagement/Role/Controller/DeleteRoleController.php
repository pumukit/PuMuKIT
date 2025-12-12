<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Role\Controller;

use App\ContentManagement\Role\Application\DeleteRole\DeleteRoleRequest;
use App\ContentManagement\Role\Application\DeleteRole\DeleteRoleService;
use App\ContentManagement\Role\Application\ViewRole\ViewRoleRequest;
use App\ContentManagement\Role\Application\ViewRole\ViewRoleService;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DeleteRoleController extends AbstractController
{
    public function __construct(
        private ViewRoleService $viewRoleService,
        private DeleteRoleService $deleteRoleService,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(string $id): Response
    {
        try {
            $viewRoleRequest = new ViewRoleRequest($id);
            $roleResponse = ($this->viewRoleService)($viewRoleRequest);
            $cod = $roleResponse->role->getCod();

            $deleteRoleRequest = new DeleteRoleRequest($id);
            ($this->deleteRoleService)($deleteRoleRequest);

            $this->addFlash('success', $this->translator->trans(
                'role.flash.deleted',
                ['%cod%' => $cod],
                'role'
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans(
                'role.error.delete_failed',
                ['%message%' => $e->getMessage()],
                'role'
            ));
        }

        return $this->redirectToRoute('role_list');
    }
}
