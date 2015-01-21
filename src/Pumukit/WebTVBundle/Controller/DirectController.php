<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DirectController extends Controller
{
    /**
     * @Route("/direct")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }
}