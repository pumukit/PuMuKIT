<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class TranslateController implements NewAdminControllerInterface
{
    public function indexAction(): JsonResponse
    {
        return new JsonResponse(['status' => 'Not Implemented'], 501);
    }
}
