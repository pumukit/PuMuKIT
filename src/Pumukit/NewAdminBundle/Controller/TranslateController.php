<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TranslateController implements NewAdminControllerInterface
{
    /**
     * Translate controller.
     * Declared as a service to be extended.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function indexAction(Request $request)
    {
        return new JsonResponse(['status' => 'Not Implemented'], 501);
    }
}
