<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Infrastructure\Ui\Backoffice\Http\Controller;

use App\ContentManagement\Role\Application\ViewRole\ViewRoleRequest;
use App\ContentManagement\Role\Application\ViewRole\ViewRoleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ViewRoleController extends AbstractController
{
    public function __construct(
        private ViewRoleService $viewRoleService
    ) {}

    public function __invoke(string $id): Response
    {
        $viewRoleRequest = new ViewRoleRequest($id);
        $roleResponse = ($this->viewRoleService)($viewRoleRequest);
        $role = $roleResponse->role;

        return $this->render('@Role/Views/view.html.twig', [
            'role' => $role,
        ]);
    }
}
