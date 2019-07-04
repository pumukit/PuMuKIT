<?php

namespace Pumukit\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AuthController.
 */
class AuthController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/auth", name="pumukit_auth")
     */
    public function changeAction(Request $request)
    {
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
