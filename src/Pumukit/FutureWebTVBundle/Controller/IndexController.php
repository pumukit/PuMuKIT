<?php

namespace Pumukit\FutureWebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class IndexController.
 */
class IndexController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/", name="pumukit_webtv_index_index")
     * @Template("PumukitFutureWebTVBundle:Index:template.html.twig")
     */
    public function indexAction()
    {
        $this->get('pumukit_web_tv.breadcrumbs')->reset();

        return [
            'menu_stats' => $this->container->getParameter('menu.show_stats'),
        ];
    }
}
