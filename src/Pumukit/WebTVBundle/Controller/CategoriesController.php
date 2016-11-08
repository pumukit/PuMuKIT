<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

//Used on countMmobjsInTags TODO Move to service
use Pumukit\SchemaBundle\Document\MultimediaObject;

class CategoriesController extends Controller implements WebTVController
{
    /**
     * @Route("/categories/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date|tags"}, name="pumukit_webtv_categories_index")
     * @Template()
     */
    public function indexAction($sort, Request $request)
    {
        $templateTitle = $this->container->getParameter('menu.categories_title');
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle?:'Videos by Category', 'pumukit_webtv_categories_index');
        $parentCod = $this->container->getParameter('categories_tag_cod');

        $groundsRoot = $this->getDoctrine()
                              ->getRepository('PumukitSchemaBundle:Tag')
                              ->findOneByCod($parentCod);

        if (!isset($groundsRoot)) {
            throw $this->createNotFoundException('The parent with cod: '.$parentCod.' was not found. Please add it to the Tags database or configure another categories_tag_cod in parameters.yml');
        }

        $listGeneralParam = $this->container->getParameter('categories.list_general_tags');

        $allGrounds = array();
        $tagsTree = $this->getDoctrine()
                         ->getRepository('PumukitSchemaBundle:Tag')
                         ->getTree($groundsRoot);

        //Create array structure
        //TODO Move this logic to a service.
        $tagsArray = array();
        $parentPathLength = strlen($groundsRoot->getPath());
        foreach ($tagsTree as $tag) {
            $path = sprintf('%s__object', $tag->getPath());
            $keys = explode('|', $path);
            $ref = & $tagsArray;
            foreach ($keys as $key) {
                if (!array_key_exists($key, $ref)) {
                    $ref[$key] = array();
                }
                $ref = & $ref[$key];
            }
            $ref = $tag;
        }
        //Removes unnecessary parent nodes.
        $parentKeys = explode('|', substr($groundsRoot->getPath(), 0, -1));
        $ref = & $tagsArray;
        foreach ($parentKeys as $key) {
            $ref = & $ref[$key];
        }
        $tagsArray = $ref;
        //End removes unnecessary parent nodes.

        //Count number multimediaObjects
        $provider = $request->get('provider');
        //TODO Move this logic into a service.
        $counterMmobjs = $this->countMmobjInTags($provider);
        $linkService = $this->get('pumukit_web_tv.link_service');
        foreach ($tagsArray as $id => $parent) {
            if ($id == '__object') {
                continue;
            }
            $allGrounds[$id] = array();
            $allGrounds[$id]['title'] = $parent['__object']->getTitle();
            $allGrounds[$id]['url'] = $linkService->generatePathToTag($parent['__object']->getCod(), null, array('tags[]' => $provider));
            $numMmobjs = 0;
            $cod = $parent['__object']->getCod();
            if (isset($counterMmobjs[$cod])) {
                $numMmobjs = $counterMmobjs[$cod];
            }
            $allGrounds[$id]['num_mmobjs'] = $numMmobjs;
            $allGrounds[$id]['children'] = array();

            //Add 'General' Tag
            if ($listGeneralParam) {
                $allGrounds[$id]['children']['general'] = array();
                $allGrounds[$id]['children']['general']['title'] = $this->get('translator')->trans('General %title%', array('%title%' => $parent['__object']->getTitle()));
                $allGrounds[$id]['children']['general']['url'] = $linkService->generatePathToTag($parent['__object']->getCod(), true, array('tags[]' => $provider));
                $numMmobjs = 0;
                if (isset($counterGeneralMmobjs[$cod])) {
                    $numMmobjs = $counterMmobjs[$cod];
                }
                $allGrounds[$id]['children']['general']['num_mmobjs'] = $this->countGeneralMmobjsInTag($parent['__object'], $provider);
                $allGrounds[$id]['children']['general']['children'] = array();
            }
            foreach ($parent as $id2 => $child) {
                if ($id2 == '__object') {
                    continue;
                }
                $allGrounds[$id]['children'][$id2] = array();
                $allGrounds[$id]['children'][$id2]['title'] = $child['__object']->getTitle();
                $allGrounds[$id]['children'][$id2]['url'] = $linkService->generatePathToTag($child['__object']->getCod(), null, array('tags[]' => $provider));

                $numMmobjs = 0;
                $cod = $child['__object']->getCod();
                if (isset($counterMmobjs[$cod])) {
                    $numMmobjs = $counterMmobjs[$cod];
                }
                $allGrounds[$id]['children'][$id2]['num_mmobjs'] = $numMmobjs;
                $allGrounds[$id]['children'][$id2]['children'] = array();

                foreach ($child as $id3 => $grandchild) {
                    if ($id3 == '__object') {
                        continue;
                    }
                    $allGrounds[$id]['children'][$id2]['children'][$id3] = array();
                    $allGrounds[$id]['children'][$id2]['children'][$id3]['title'] = $grandchild['__object']->getTitle();
                    $allGrounds[$id]['children'][$id2]['children'][$id3]['url'] = $linkService->generatePathToTag($grandchild['__object']->getCod(), null, array('tags[]' => $provider));
                    $numMmobjs = 0;
                    $cod = $grandchild['__object']->getCod();
                    if (isset($counterMmobjs[$cod])) {
                        $numMmobjs = $counterMmobjs[$cod];
                    }
                    $allGrounds[$id]['children'][$id2]['children'][$id3]['num_mmobjs'] = $numMmobjs;
                }
            }
        }
        return array('allGrounds' => $allGrounds, 'title' => $groundsRoot->getTitle(), 'list_general_tags' => $listGeneralParam );
    }

    //TODO Move this function into a service.
    private function countMmobjInTags($provider = null)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $multimediaObjectsColl = $dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $criteria = array('status' => MultimediaObject::STATUS_PUBLISHED, 'tags.cod' => 'PUCHWEBTV', 'tags.cod' => 'ITUNESU');
        $criteria['$or'] = array(
             array('tracks' => array('$elemMatch' => array('tags' => 'display', 'hide' => false)), 'properties.opencast' => array('$exists' => false)),
             array('properties.opencast' => array('$exists' => true)),
        );
        if ($provider !== null) {
            $criteria['$and'] = array(
                array('tags.cod' => array('$eq' => $provider)), );
        }
        $pipeline = array(
            array('$match' => $criteria),
            array('$unwind' => '$tags' ),
            array('$group' => array('_id' => '$tags.cod', 'count' => array('$sum' => 1))),
        );

        $aggregation = $multimediaObjectsColl->aggregate($pipeline);
        $mmobjCount = array();
        foreach ($aggregation as $a) {
            $mmobjCount[(string)$a['_id']] = $a['count'];
        }
        return $mmobjCount;
    }

    //TODO Move this function into a service.
    private function countGeneralMmobjsInTag($tag, $provider = null)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $qb = $repo->createBuilderWithGeneralTag($tag);
        if ($provider !== null) {
            $qb = $qb->field('tags.cod')->equals($provider);
        }
        $qb = $qb->count()
                 ->getQuery()
                 ->execute();

        return $qb;
    }
}
