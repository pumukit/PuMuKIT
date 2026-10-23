<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait UploadResponseTrait
{
    private function uploadResponse(Request $request, array $result): array|JsonResponse
    {
        if ('json' !== $request->getPreferredFormat()) {
            return $result;
        }

        return $this->uploadJsonResponse($result['uploaded'], $result['message']);
    }

    private function uploadJsonResponse(string $uploaded, string $message): JsonResponse
    {
        return new JsonResponse([
            'uploaded' => $uploaded,
            'message' => $message,
        ]);
    }
}
