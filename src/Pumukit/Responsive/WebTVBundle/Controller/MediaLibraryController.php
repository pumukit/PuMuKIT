<?php

namespace Pumukit\Responsive\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class MediaLibraryController extends Controller
{
    /**
     * @Route("/mediateca/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date"}, name="pumukit_responsive_webtv_medialibrary_index")
     * @Template()
     */
    public function indexAction($sort, Request $request)
    {
        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

        $sortField = "alphabetically" == $sort ? 'title.' . $request->getLocale() : "public_date";
        $series = $repo->findBy(array(), array($sortField => 1));        

        $this->get('pumukit_responsive_web_tv.breadcrumbs')->addList("All", "pumukit_responsive_webtv_medialibrary_index");

        return array('series' => $series, 'sort' => $sort);
    }
}