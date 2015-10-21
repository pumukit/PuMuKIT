<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class CategoriesController extends Controller
{
    /**
     * @Route("/categories/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date|tags"}, name="pumukit_webtv_categories_index")
     * @Template()
     */
    public function indexAction($sort, Request $request)
    {
        $this->get('pumukit_web_tv.breadcrumbs')->addList('Videos by Category', 'pumukit_webtv_categories_index');

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
}
