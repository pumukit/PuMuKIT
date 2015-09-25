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
    public function userAction(Request $request, $opencast=false)
    {
        $casService = $this->get('pumukit_cmar_web_tv.casservice');
        $casIsAuthenticated = $casService->isAuthenticated();
        if ($casIsAuthenticated) {
            $username = $casService->getUser();
        } else {
            $username = '';
        }

        if ($opencast) {
            return $this->render('PumukitCmarWebTVBundle:Utils:opencast_user.html.twig',
                                 array(
                                       'cas_is_authenticated' => $casIsAuthenticated,
                                       'cas_username' => $username
                                       )
                                 )
              ;
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
        $casService = $this->get('pumukit_cmar_web_tv.casservice');
        $url = $request->server->get("HTTP_REFERER");
        $casService->setFixedServiceURL($url);
        $casService->forceAuthentication();
        if(!in_array($casService->getUser(), array("tv", "prueba", "adminmh", "admin", "sistemas.uvigo"))) {
            throw $this->createAccessDeniedException('Unable to access this page!');        
        }

        return $this->redirect($url);
    }

    /**
     * @Route("/logout", name="pumukit_cmar_web_tv_utils_logout")
     */
    public function logoutAction(Request $request)
    {
        $casService = $this->get('pumukit_cmar_web_tv.casservice');
        $url = $this->generateUrl('pumukit_webtv_index_index', array(), true);
        $casService->logoutWithRedirectService($url);

        return $this->redirect($url);
    }
}