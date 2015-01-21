<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FaqController extends Controller
{
    /**
     * @Route("/faq")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }
}