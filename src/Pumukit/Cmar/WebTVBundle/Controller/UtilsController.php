<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\JsonResponse;

/**
 * @Route("/utils")
 */
class UtilsController extends Controller
{
    /**
     * @Template()
     */
    public function userAction(Request $request)
    {
        $casIsAuthenticated = \phpCAS::isAuthenticated();
        if ($casIsAuthenticated) {
            $username = \phpCAS::getUser();
        } else {
            $username = '';
        }

        return array(
                     'cas_is_authenticated' => $casIsAuthenticated,
                     'cas_username' => $username
                     );
    }

    /**
     * @Route("/login", name="pumukit_cmar_web_tv_utils_login")
     */
    public function loginAction(Request $request)
    {
        \phpCAS::forceAuthentication();
        if(!in_array(\phpCAS::getUser(), array("tv", "prueba", "adminmh", "admin", "sistemas.uvigo"))) {
            throw $this->createAccessDeniedException('Unable to access this page!');        
        }

        $url = $this->generateUrl('pumukit_webtv_index_index');

        return $this->redirect($url);
    }

    /**
     * @Route("/logout", name="pumukit_cmar_web_tv_utils_logout")
     */
    public function logoutAction(Request $request)
    {
        $url = $this->generateUrl('pumukit_webtv_index_index', array(), true);
        \phpCAS::logoutWithRedirectService($url);

        return $this->redirect($url);
    }
}