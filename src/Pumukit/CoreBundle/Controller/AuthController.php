<?php

namespace Pumukit\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AuthController.
 */
class AuthController extends AbstractController implements WebTVControllerInterface
{
    /**
     * @Route("/auth", name="pumukit_auth")
     */
    public function changeAction(Request $request): RedirectResponse
    {
        /** @var SessionInterface */
        $session = $this->get('session');

        if (!$session->has('target_path')) {
            $referer = $request->headers->get('referer', '/');
            $session->set('target_path', $request->query->get('referer', $referer));
        }

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException('Unable to access this page!');
        }

        $targetUrl = $session->get('target_path');
        $session->remove('target_path');

        return $this->redirect($targetUrl);
    }
}
