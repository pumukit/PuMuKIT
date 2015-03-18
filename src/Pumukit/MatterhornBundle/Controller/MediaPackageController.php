<?php

namespace Pumukit\MatterhornBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class MediaPackageController extends Controller
{
    /**
     * @Route("/matterhorn")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $limit = 5;
        $page =  $request->get("page", 1);

    	$mediaPackages = $this->get('pumukit_matterhorn.client')->getMediaPackages(0,$limit,$page);
    	//var_dump($mediaPackages);

        $adapter = new ArrayAdapter($mediaPackages);
        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return array('mediaPackages' => $pagerfanta);
    }
}