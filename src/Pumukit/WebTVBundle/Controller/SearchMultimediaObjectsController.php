<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Element;


class SearchMultimediaObjectsController extends Controller
{
    /**
     * @Route("/searchmultimediaobjects")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }
}