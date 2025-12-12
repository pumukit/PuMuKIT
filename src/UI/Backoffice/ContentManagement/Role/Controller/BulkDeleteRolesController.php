<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Role\Controller;

use App\ContentManagement\Role\Application\DeleteRole\DeleteRoleRequest;
use App\ContentManagement\Role\Application\DeleteRole\DeleteRoleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkDeleteRolesController extends AbstractController
{
    public function __construct(
        private DeleteRoleService $deleteRoleService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No roles selected for deletion',
            ], 400);
        }

        $deleted = 0;
        $failed = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $deleteRoleRequest = new DeleteRoleRequest($id);
                ($this->deleteRoleService)($deleteRoleRequest);
                ++$deleted;
            } catch (\Exception $e) {
                ++$failed;
                $errors[] = sprintf('Role %s: %s', $id, $e->getMessage());
            }
        }

        if ($deleted > 0 && 0 === $failed) {
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%d role(s) deleted successfully', $deleted),
            ]);
        }
        if ($deleted > 0 && $failed > 0) {
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%d role(s) deleted, %d failed', $deleted, $failed),
                'errors' => $errors,
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Failed to delete roles',
            'errors' => $errors,
        ], 500);
    }
}
