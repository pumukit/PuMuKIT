<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
//Used on countMmobjsInTags TODO Move to service
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\Tag;

/**
 * Class CategoriesController.
 */
class CategoriesController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/categories/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date|tags"}, name="pumukit_webtv_categories_index")
     * @Template("PumukitWebTVBundle:Categories:template.html.twig")
     */
    public function indexAction($sort, Request $request)
    {
        $templateTitle = $this->container->getParameter('menu.categories_title');
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle ?: 'Videos by Category', 'pumukit_webtv_categories_index');
        $parentCod = $this->container->getParameter('categories_tag_cod');

        $groundsRoot = $this->getDoctrine()
            ->getRepository(Tag::class)
            ->findOneByCod($parentCod);

        if (!isset($groundsRoot)) {
            throw $this->createNotFoundException(
                'The parent with cod: '.$parentCod
                .' was not found. Please add it to the Tags database or configure another categories_tag_cod in parameters.yml'
            );
        }

        $listGeneralParam = $this->container->getParameter('categories.list_general_tags');

        $allGrounds = [];
        $tagsTree = $this->getDoctrine()
            ->getRepository(Tag::class)
            ->getTree($groundsRoot);

        //Create array structure
        //TODO Move this logic to a service.
        $tagsArray = [];
        $parentPathLength = strlen($groundsRoot->getPath());
        foreach ($tagsTree as $tag) {
            $path = sprintf('%s__object', $tag->getPath());
            $keys = explode('|', $path);
            $ref = &$tagsArray;
            foreach ($keys as $key) {
                if (!array_key_exists($key, $ref)) {
                    $ref[$key] = [];
                }
                $ref = &$ref[$key];
            }
            $ref = $tag;
        }
        //Removes unnecessary parent nodes.
        $parentKeys = explode('|', substr($groundsRoot->getPath(), 0, -1));
        $ref = &$tagsArray;
        foreach ($parentKeys as $key) {
            $ref = &$ref[$key];
        }
        $tagsArray = $ref;
        //End removes unnecessary parent nodes.

        //Count number multimediaObjects
        $provider = $request->get('provider');
        //TODO Move this logic into a service.
        $counterMmobjs = $this->countMmobjInTags($provider);
        $linkService = $this->get('pumukit_web_tv.link_service');
        foreach ($tagsArray as $id => $parent) {
            if ('__object' == $id) {
                continue;
            }
            $allGrounds[$id] = [];
            $allGrounds[$id]['title'] = $parent['__object']->getTitle();
            $allGrounds[$id]['url'] = $linkService->generatePathToTag($parent['__object']->getCod(), null, ['tags[]' => $provider]);
            $numMmobjs = 0;
            $cod = $parent['__object']->getCod();
            if (isset($counterMmobjs[$cod])) {
                $numMmobjs = $counterMmobjs[$cod];
            }
            $allGrounds[$id]['num_mmobjs'] = $numMmobjs;
            $allGrounds[$id]['children'] = [];

            //Add 'General' Tag
            if ($listGeneralParam) {
                $allGrounds[$id]['children']['general'] = [];
                $allGrounds[$id]['children']['general']['title'] = $this->get('translator')->trans(
                    'General %title%',
                    ['%title%' => $parent['__object']->getTitle()]
                );
                $allGrounds[$id]['children']['general']['url'] = $linkService->generatePathToTag(
                    $parent['__object']->getCod(),
                    true,
                    ['tags[]' => $provider]
                );
                $numMmobjs = 0;
                if (isset($counterGeneralMmobjs[$cod])) {
                    $numMmobjs = $counterMmobjs[$cod];
                }
                $allGrounds[$id]['children']['general']['num_mmobjs'] = $this->countGeneralMmobjsInTag($parent['__object'], $provider);
                $allGrounds[$id]['children']['general']['children'] = [];
            }
            foreach ($parent as $id2 => $child) {
                if ('__object' == $id2) {
                    continue;
                }
                $allGrounds[$id]['children'][$id2] = [];
                $allGrounds[$id]['children'][$id2]['title'] = $child['__object']->getTitle();
                $allGrounds[$id]['children'][$id2]['url'] = $linkService->generatePathToTag(
                    $child['__object']->getCod(),
                    null,
                    ['tags[]' => $provider]
                );

                $numMmobjs = 0;
                $cod = $child['__object']->getCod();
                if (isset($counterMmobjs[$cod])) {
                    $numMmobjs = $counterMmobjs[$cod];
                }
                $allGrounds[$id]['children'][$id2]['num_mmobjs'] = $numMmobjs;
                $allGrounds[$id]['children'][$id2]['children'] = [];

                foreach ($child as $id3 => $grandchild) {
                    if ('__object' == $id3) {
                        continue;
                    }
                    $allGrounds[$id]['children'][$id2]['children'][$id3] = [];
                    $allGrounds[$id]['children'][$id2]['children'][$id3]['title'] = $grandchild['__object']->getTitle();
                    $allGrounds[$id]['children'][$id2]['children'][$id3]['url'] = $linkService->generatePathToTag(
                        $grandchild['__object']->getCod(),
                        null,
                        ['tags[]' => $provider]
                    );
                    $numMmobjs = 0;
                    $cod = $grandchild['__object']->getCod();
                    if (isset($counterMmobjs[$cod])) {
                        $numMmobjs = $counterMmobjs[$cod];
                    }
                    $allGrounds[$id]['children'][$id2]['children'][$id3]['num_mmobjs'] = $numMmobjs;
                }
            }
        }

        return ['allGrounds' => $allGrounds, 'title' => $groundsRoot->getTitle(), 'list_general_tags' => $listGeneralParam];
    }

    //TODO Move this function into a service.
    private function countMmobjInTags($provider = null)
    {
        $parentCod = $this->container->getParameter('categories_tag_cod');

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $multimediaObjectsColl = $dm->getDocumentCollection(MultimediaObject::class);
        $criteria = ['status' => MultimediaObject::STATUS_PUBLISHED, 'tags.cod' => ['$all' => ['PUCHWEBTV', $parentCod]]];
        $criteria['$or'] = [
            ['tracks' => ['$elemMatch' => ['tags' => 'display', 'hide' => false]], 'properties.opencast' => ['$exists' => false]],
            ['properties.opencast' => ['$exists' => true]],
            ['properties.externalplayer' => ['$exists' => true, '$ne' => '']],
        ];
        if (null !== $provider) {
            $criteria['$and'] = [
                ['tags.cod' => ['$eq' => $provider]],
            ];
        }
        $pipeline = [
            ['$match' => $criteria],
            ['$unwind' => '$tags'],
            ['$group' => ['_id' => '$tags.cod', 'count' => ['$sum' => 1]]],
        ];

        $aggregation = $multimediaObjectsColl->aggregate($pipeline, ['cursor' => []]);
        $mmobjCount = [];
        foreach ($aggregation as $a) {
            $mmobjCount[(string) $a['_id']] = $a['count'];
        }

        return $mmobjCount;
    }

    //TODO Move this function into a service.
    private function countGeneralMmobjsInTag($tag, $provider = null)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository(MultimediaObject::class);
        $qb = $repo->createBuilderWithGeneralTag($tag);
        if (null !== $provider) {
            $qb = $qb->field('tags.cod')->equals($provider);
        }
        $qb = $qb->count()
            ->getQuery()
            ->execute();

        return $qb;
    }
}
