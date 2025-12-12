<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Person\Controller;

use App\ContentManagement\Person\Application\DeletePerson\DeletePersonRequest;
use App\ContentManagement\Person\Application\DeletePerson\DeletePersonService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkDeletePersonsController extends AbstractController
{
    public function __construct(
        private DeletePersonService $deletePersonService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No persons selected for deletion',
            ], 400);
        }

        $deleted = 0;
        $failed = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $deletePersonRequest = new DeletePersonRequest($id);
                ($this->deletePersonService)($deletePersonRequest);
                ++$deleted;
            } catch (\Exception $e) {
                ++$failed;
                $errors[] = sprintf('Person %s: %s', $id, $e->getMessage());
            }
        }

        if ($deleted > 0 && 0 === $failed) {
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%d person(s) deleted successfully', $deleted),
            ]);
        }
        if ($deleted > 0 && $failed > 0) {
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%d person(s) deleted, %d failed', $deleted, $failed),
                'errors' => $errors,
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Failed to delete persons',
            'errors' => $errors,
        ], 500);
    }
}
