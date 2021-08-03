<?php

namespace Pumukit\Prueba\WebTVBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
        return $this->render('PumukitPruebaWebTVBundle:layout.html.twig');
    }
}
