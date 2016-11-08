<?php

namespace Pumukit\Legacy\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class IndexController extends Controller
{
    /**
     * @Route("/", name="pumukit_webtv_index_index")
     * @Template()
     */
    public function indexAction()
    {
        $this->get('pumukit_web_tv.breadcrumbs')->reset();
        return array();
    }

    /**
     * @Template()
     */
    public function infoAction()
    {
        return array();
    }

    /**
     * @Template()
     */
    public function categoriesAction()
    {
        return array();
    }


    /**
     * @Template()
     */
    public function mostviewedAction()
    {
        $repository = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $multimediaObjectsSortedByNumview = $repository->findStandardBy(array(), array('numview' => -1), 3, 0);
        return array('multimediaObjectsSortedByNumview' => $multimediaObjectsSortedByNumview);
    }

    /**
     * @Template("PumukitWebTVBundle:Index:mostviewed.html.twig")
     */
    public function mostviewedlastmonthAction()
    {
        $multimediaObjectsSortedByNumview = $this->get('pumukit_stats.stats')->getMostViewedUsingFilters(30, 3);
        return array('multimediaObjectsSortedByNumview' => $multimediaObjectsSortedByNumview);
    }

    /**
     * @Template()
     */
    public function recentlyaddedAction()
    {
        $last = $this->get('pumukitschema.announce')->getLast(3);
        return array('last' => $last);
    }

    /**
     * @Template()
     */
    public function newsAction()
    {
        return array();
    }
}
