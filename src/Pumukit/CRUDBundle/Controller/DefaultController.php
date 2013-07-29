<?php

namespace Pumukit\CRUDBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
  
    /**
     * @Route("/hello/{name}")
     * @Template("PumukitCRUDBundle:Default:index.html.twig")
     */
    public function indexAction($name)
    {
        return array('name' => $name);
    }
    /**
     * @Route("/sum/{a}/{b}")
     * @Template("PumukitCRUDBundle:Default:sum.html.twig")
     */
    public function sumAction($a, $b /*, \Symfony\Component\HttpFoundation\Request $req*/)
    {
        //var_dump($req->getPara()); exit;
      
        $sum = $a + $b;
        return array('sum' => $sum);
    }
}
