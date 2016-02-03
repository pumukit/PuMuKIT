<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/layout")
 */
class LayoutController extends Controller
{
    /**
     * @Route("/logo")
     */
    public function logoAction()
    {
        $info = $this->container->getParameter('pumukit2.info');
        if (null == $info) {
            throw new \Exception('Mandatory to add logo in parameters');
        }
        $logo = isset($info['logo']) ? $info['logo'] : '';

        return $this->redirect($logo);
    }
}