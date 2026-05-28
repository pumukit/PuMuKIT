<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController implements WebTVControllerInterface
{
    /**
     * @Route("/auth", name="pumukit_auth")
     */
    public function changeAction(Request $request): RedirectResponse
    {
        if (!$request->getSession()->has('target_path')) {
            $referer = $request->headers->get('referer', '/');
            $request->getSession()->set('target_path', $request->query->get('referer', $referer));
        }

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException('Unable to access this page!');
        }

        $targetUrl = $request->getSession()->get('target_path');
        $request->getSession()->remove('target_path');

        return $this->redirect($targetUrl);
    }
}
