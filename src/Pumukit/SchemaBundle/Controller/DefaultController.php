<?php

namespace Pumukit\SchemaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/hola/{name}")
     * @Template("PumukitSchemaBundle:Default:index.html.twig")
     */
    public function indexAction($name)
    {
        return array('name' => $name);
    }
    /**
     * @Route("/")
     * @Template("PumukitSchemaBundle:Default:index.html.twig")
     */
    public function holaAnonimoAction()
    {
        return array('name' => "anonimo");
    }
}
