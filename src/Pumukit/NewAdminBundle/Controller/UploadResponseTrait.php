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

        return new JsonResponse([
            'uploaded' => $result['uploaded'],
            'message' => $result['message'],
        ]);
    }
}
