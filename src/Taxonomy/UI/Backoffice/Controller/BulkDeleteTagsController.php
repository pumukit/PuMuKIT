<?php

declare(strict_types=1);

namespace App\Taxonomy\UI\Backoffice\Controller;

use App\Taxonomy\Application\DeleteTag\DeleteTagRequest;
use App\Taxonomy\Application\DeleteTag\DeleteTagService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BulkDeleteTagsController extends AbstractController
{
    public function __construct(
        private DeleteTagService $deleteTagService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No tags selected for deletion',
            ], 400);
        }

        $deleted = 0;
        $failed = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $deleteTagRequest = new DeleteTagRequest($id);
                ($this->deleteTagService)($deleteTagRequest);
                ++$deleted;
            } catch (\Exception $e) {
                ++$failed;
                $errors[] = sprintf('Tag %s: %s', $id, $e->getMessage());
            }
        }

        if ($deleted > 0 && 0 === $failed) {
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%d tag(s) deleted successfully', $deleted),
            ]);
        }
        if ($deleted > 0 && $failed > 0) {
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%d tag(s) deleted, %d failed', $deleted, $failed),
                'errors' => $errors,
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Failed to delete tags',
            'errors' => $errors,
        ], 500);
    }
}
