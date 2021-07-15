<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class PaginationService.
 */
class CategoriesService
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var LinkService
     */
    private $linkService;

    /**
     * @var TranslatorInterface
     */
    private $translator;
    private $parentCod;
    private $listGeneralParam;
    private $excludeEmptyTags;

    public function __construct(DocumentManager $documentManager, LinkService $linkService, TranslatorInterface $translator, $parentCod, $listGeneralParam, $excludeEmptyTags)
    {
        $this->documentManager = $documentManager;
        $this->parentCod = $parentCod;
        $this->translator = $translator;
        $this->listGeneralParam = $listGeneralParam;
        $this->linkService = $linkService;
        $this->excludeEmptyTags = $excludeEmptyTags;
    }

    public function getCategoriesElements($provider, $parentCod = null)
    {
        $parentCod = $parentCod ?? $this->parentCod;
        $groundsRoot = $this->documentManager
            ->getRepository(Tag::class)
            ->findOneByCod($parentCod)
        ;

        if (!isset($groundsRoot)) {
            throw new \Exception('The parent with cod: '.$parentCod.' was not found. Please add it to the Tags database or configure another categories_tag_cod in parameters.yml');
        }

        $allGrounds = [];
        /** @var Tag[] */
        $tagsTree = $this->documentManager
            ->getRepository(Tag::class)
            ->getTree($groundsRoot)
        ;

        $tagsArray = [];
        foreach ($tagsTree as $tag) {
            $path = sprintf('%s__object', $tag->getPath());
            $keys = explode('|', $path);
            $ref = &$tagsArray;
            foreach ($keys as $key) {
                if (!isset($ref[$key])) {
                    $ref[$key] = [];
                }
                $ref = &$ref[$key];
            }
            $ref = $tag;
        }

        // Removes unnecessary parent nodes.
        $parentKeys = explode('|', substr($groundsRoot->getPath(), 0, -1));
        $ref = &$tagsArray;
        foreach ($parentKeys as $key) {
            $ref = &$ref[$key];
        }
        $tagsArray = $ref;

        $counterMmobjs = $this->countMmobjInTags($provider, $parentCod);
        foreach ($tagsArray as $id => $parent) {
            if ('__object' == $id) {
                continue;
            }
            $cod = $parent['__object']->getCod();
            $numMmobjs = 0;
            if (isset($counterMmobjs[$cod])) {
                $numMmobjs += $counterMmobjs[$cod];
            }
            if ($this->excludeEmptyTags and $numMmobjs <= 0) {
                continue;
            }
            $allGrounds[$id] = [];
            $allGrounds[$id]['title'] = $parent['__object']->getTitle();
            $allGrounds[$id]['url'] = $this->linkService->generatePathToTag($parent['__object']->getCod(), null, ['tags[]' => $provider]);
            $allGrounds[$id]['num_mmobjs'] = $numMmobjs;
            $allGrounds[$id]['children'] = [];

            $numMmobjsGeneral = $this->countGeneralMmobjsInTag($parent['__object'], $provider);
            //Add 'General' Tag
            if ($this->listGeneralParam && (!$this->excludeEmptyTags || $numMmobjsGeneral > 0)) {
                $allGrounds[$id]['children']['general'] = [];
                $allGrounds[$id]['children']['general']['title'] = $this->translator->trans(
                    'General %title%',
                    ['%title%' => $parent['__object']->getTitle()]
                );
                $allGrounds[$id]['children']['general']['url'] = $this->linkService->generatePathToTag(
                    $parent['__object']->getCod(),
                    true,
                    ['tags[]' => $provider]
                );
                $allGrounds[$id]['children']['general']['num_mmobjs'] = $numMmobjsGeneral;
                $allGrounds[$id]['children']['general']['children'] = [];
            }
            foreach ($parent as $id2 => $child) {
                if ('__object' == $id2) {
                    continue;
                }
                $numMmobjs = 0;
                $cod = $child['__object']->getCod();
                if (isset($counterMmobjs[$cod])) {
                    $numMmobjs += $counterMmobjs[$cod];
                }
                if ($this->excludeEmptyTags and $numMmobjs <= 0) {
                    continue;
                }
                $allGrounds[$id]['children'][$id2] = [];
                $allGrounds[$id]['children'][$id2]['title'] = $child['__object']->getTitle();
                $allGrounds[$id]['children'][$id2]['url'] = $this->linkService->generatePathToTag(
                    $child['__object']->getCod(),
                    null,
                    ['tags[]' => $provider]
                );

                $allGrounds[$id]['children'][$id2]['num_mmobjs'] = $numMmobjs;
                $allGrounds[$id]['children'][$id2]['children'] = [];

                foreach ($child as $id3 => $grandchild) {
                    if ('__object' == $id3) {
                        continue;
                    }
                    $allGrounds[$id]['children'][$id2]['children'][$id3] = [];
                    $allGrounds[$id]['children'][$id2]['children'][$id3]['title'] = $grandchild['__object']->getTitle();
                    $allGrounds[$id]['children'][$id2]['children'][$id3]['url'] = $this->linkService->generatePathToTag(
                        $grandchild['__object']->getCod(),
                        null,
                        ['tags[]' => $provider]
                    );
                    $numMmobjs = 0;
                    $cod = $grandchild['__object']->getCod();
                    if (isset($counterMmobjs[$cod])) {
                        $numMmobjs += $counterMmobjs[$cod];
                    }
                    $allGrounds[$id]['children'][$id2]['children'][$id3]['num_mmobjs'] = $numMmobjs;
                }
            }
        }

        return [
            $allGrounds,
            $groundsRoot->getTitle(),
        ];
    }

    /**
     * @param string|null $provider
     * @param mixed|null  $parentCod
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @return array
     */
    private function countMmobjInTags($provider = null, $parentCod = null)
    {
        $parentCod = $parentCod ?? $this->parentCod;
        $multimediaObjectsColl = $this->documentManager->getDocumentCollection(MultimediaObject::class);
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

    /**
     * @return mixed
     */
    private function countGeneralMmobjsInTag(Tag $tag, string $provider = null)
    {
        $repo = $this->documentManager->getRepository(MultimediaObject::class);
        $qb = $repo->createBuilderWithGeneralTag($tag);
        if (null !== $provider) {
            $qb = $qb->field('tags.cod')->equals($provider);
        }

        return $qb->count()
            ->getQuery()
            ->execute()
            ;
    }
}
