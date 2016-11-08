<?php

namespace Pumukit\Legacy\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class MediaLibraryController extends Controller
{
    /**
     * @Route("/mediateca/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date"}, name="pumukit_webtv_medialibrary_index")
     * @Template()
     */
    public function indexAction($sort, Request $request)
    {
        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

        $sortField = 'alphabetically' == $sort ? 'title.' . $request->getLocale() : 'public_date';
        $criteria = $request->query->get('search', false) ?
          array('title.' . $request->getLocale() => new \MongoRegex(sprintf('/%s/i', $request->query->get('search')))):
          array();

        $series = $repo->findBy($criteria, array($sortField => 1));

        $this->get('pumukit_web_tv.breadcrumbs')->addList('All', 'pumukit_webtv_medialibrary_index', array('sort' => $sort));

        return array('series' => $series, 'sort' => $sort);
    }
}
