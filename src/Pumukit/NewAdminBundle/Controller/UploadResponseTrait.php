<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

trait UploadResponseTrait
{
    private function uploadJsonResponse(string $uploaded, string $message): JsonResponse
    {
        return new JsonResponse([
            'uploaded' => $uploaded,
            'message' => $message,
        ]);
    }
}
