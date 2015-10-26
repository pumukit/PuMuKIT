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

        $parentCod = $this->container->getParameter('categories_tag_cod');

        $groundsRoot = $this->getDoctrine()
                              ->getRepository('PumukitSchemaBundle:Tag')
                              ->findOneByCod($parentCod);

        if(!isset($groundsRoot)){
            throw $this->createNotFoundException('The parent with cod: '.$parentCod.' was not found. Please add it to the Tags database or configure another categories_tag_cod in parameters.yml');
        }

        $listGeneralParam = false;
        if($this->container->hasParameter('categories.list_general_tags')) {
            $listGeneralParam = $this->container->getParameter('categories.list_general_tags');
        }

        $allGrounds = array();

        // Use getTree to optimize queries. Two level nesting 'getChildren' creates too many queries.
        // The children/grandchildren tree should be a single query. (getDescendants()?)
        foreach ( $groundsRoot->getChildren() as $id=>$parent ){
            $allGrounds[$id] = array();
            $allGrounds[$id]['title'] = $parent->getTitle();
            $allGrounds[$id]['url'] = $this->generateUrl('pumukit_webtv_bytag_multimediaobjects', array('cod' => $parent->getCod()));
            // This data is not correct. getNumberMultimediaObjects() Returns all mmobjs. Should return only published ones. Reimplement using Mongo aggregate.
            $allGrounds[$id]['num_mmobjs'] = $parent->getNumberMultimediaObjects();
            $allGrounds[$id]['children'] = array();

            //Add 'General' Tag
            if( $listGeneralParam || true){
                $allGrounds[$id]['children']['general'] = array();
                $allGrounds[$id]['children']['general']['title'] = $this->get('translator')->trans("General %title%", array('%title%' => $parent->getTitle()));
                $allGrounds[$id]['children']['general']['url'] = $this->generateUrl('pumukit_webtv_bytag_multimediaobjects', array('cod' => $parent->getCod(), 'list_only_general' => true));
                //Dummy value
                $allGrounds[$id]['children']['general']['num_mmobjs'] = 45610913;
                $allGrounds[$id]['children']['general']['children'] = array();
            }
            foreach ($parent->getChildren() as $id2=>$child ) {
                $allGrounds[$id]['children'][$id2] = array();
                $allGrounds[$id]['children'][$id2]['title'] = $child->getTitle();
                $allGrounds[$id]['children'][$id2]['url'] = $this->generateUrl('pumukit_webtv_bytag_multimediaobjects', array('cod' => $child->getCod()));
                // This data is not correct. getNumberMultimediaObjects() Returns all mmobjs. Should return only published ones. Reimplement using Mongo aggregate.
                $allGrounds[$id]['children'][$id2]['num_mmobjs'] = $child->getNumberMultimediaObjects();
                $allGrounds[$id]['children'][$id2]['children'] = array();

                foreach($child->getChildren() as $id3=>$grandchild ){
                  $allGrounds[$id]['children'][$id2]['children'][$id3] = array();
                  $allGrounds[$id]['children'][$id2]['children'][$id3]['title'] = $grandchild->getTitle();
                  // This data is not correct. getNumberMultimediaObjects () Returns all mmobjs. Should return only published ones. Reimplement using Mongo aggregate.
                  $allGrounds[$id]['children'][$id2]['children'][$id3]['num_mmobjs'] = $grandchild->getNumberMultimediaObjects();
                }
            }
            dump($allGrounds[$id]['children']);
        }
        return array('allGrounds' => $allGrounds, 'title' => $groundsRoot->getTitle(), 'list_general_tags' => $listGeneralParam );
    }
}
