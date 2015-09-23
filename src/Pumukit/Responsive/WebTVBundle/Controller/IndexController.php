<?php

namespace Pumukit\Responsive\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class IndexController extends Controller
{
    /**
     * @Route("/", name="pumukit_responsive_webtv_index_index")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }


    /**
     * @Route("/latestuploads", name="pumukit_responsive_webtv_index_latestuploads")
     * @Template()
     */
    public function latestUploadsAction()
    {
        $last = $this->get('pumukitschema.announce')->getLast(10000000000);
        return array('last' => $last);
    }

    /**
     * @Route("/listby_category", name="pumukit_responsive_webtv_index_listbycategory")
     * @Template()
     */
    public function listByCategoryAction()
    {
        $this->get('pumukit_responsive_web_tv.breadcrumbs')->addList('Videos by Category', 'pumukit_responsive_webtv_index_listbycategory');

        $vGroundParent = $this->getDoctrine()
                              ->getRepository('PumukitSchemaBundle:Tag')
                              ->findOneByCod("VIRTUALGROUNDS");

        $allVGrounds = $vGroundParent->getChildren();

        $ground_children = array();
        foreach ( $allVGrounds as $id=>$channel ){
            
            $allChannels[$id] = array();
            $allChannels[$id]['title'] = $channel->getTitle();
            $allChannels[$id]['cod'] = $channel->getCod();
            $allChannels[$id]['children'] = array();

            foreach ($channel->getChildren() as $child ) {
                $cod = $child->getProperty('target_cod');
                $cod = isset( $cod ) ? $cod : substr( $child->getCod(), 1);

                $parentTag =$this->getDoctrine()
                                 ->getRepository('PumukitSchemaBundle:Tag')
                                 ->findOneByCod($cod);

                $allChannels[$id]['children'][] = $parentTag;
            }
        }
        return array('allVGrounds' => $allChannels);
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
    public function categoriesAction(){
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
     * @Template("PumukitResponsiveWebTVBundle:Index:mostviewed.html.twig")
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
