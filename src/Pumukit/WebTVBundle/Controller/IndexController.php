<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class IndexController extends Controller implements WebTVController
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
     * @Template("PumukitWebTVBundle:Index:mostviewed.html.twig")
     */
    public function mostviewedAction()
    {
        $limit = $this->container->getParameter('limit_objs_mostviewed');
        $showLastMonth = $this->container->getParameter('show_mostviewed_lastmonth');

        $repository = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        if ($showLastMonth) {
            $multimediaObjectsSortedByNumview = $this->get('pumukit_stats.stats')->getMostViewedUsingFilters(30, $limit);
        } else {
            $multimediaObjectsSortedByNumview = $repository->findStandardBy(array(), array('numview' => -1), $limit, 0);
        }

        return array('multimediaObjectsSortedByNumview' => $multimediaObjectsSortedByNumview,
                     'show_last_month' => $showLastMonth,
        );
    }

    /**
     * @Template()
     */
    public function recentlyaddedAction()
    {
        $limit = $this->container->getParameter('limit_objs_recentlyadded');
        $showPudenew = $this->container->getParameter('show_latest_with_pudenew');
        $last = $this->get('pumukitschema.announce')->getLast($limit, $showPudenew);

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
