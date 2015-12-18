<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

class SearchController extends Controller
{
    /**
     * @Route("/searchseries")
     * @Template("PumukitWebTVBundle:Search:index.html.twig")
     */
    public function seriesAction(Request $request)
    {
        $numberCols = 2;
        if ($this->container->hasParameter('columns_objs_search')) {
            $numberCols = $this->container->getParameter('columns_objs_search');
        }

        $this->get('pumukit_web_tv.breadcrumbs')->addList('Series search', 'pumukit_webtv_search_series');

        $searchFound = $request->query->get('search');
        $startFound = $request->query->get('start');
        $endFound = $request->query->get('end');

        $repository_series = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

        $queryBuilder = $repository_series->createQueryBuilder();

        if ($searchFound != '') {
            $queryBuilder->field('$text')->equals(array('$search' => $searchFound));
        }

        if ($startFound != 'All' && $startFound != '') {
            $start = \DateTime::createFromFormat('d/m/Y', $startFound);
            $queryBuilder->field('public_date')->gt($start);
        }

        if ($endFound != 'All' && $endFound != '') {
            $end = \DateTime::createFromFormat('d/m/Y', $endFound);
            $queryBuilder->field('public_date')->lt($end);
        }

        $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));

        return array('type' => 'series',
        'objects' => $pagerfanta,
        'number_cols' => $numberCols, );
    }

    /**
     * @Route("/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}", defaults={"tagCod": null, "useTagAsGeneral": false})
     * @ParamConverter("blockedTag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitWebTVBundle:Search:index.html.twig")
     */
    public function multimediaObjectsAction(Request $request, Tag $blockedTag = null, $useTagAsGeneral = false)
    {
        $templateTitle = null;
        if($this->container->hasParameter('menu.search_title')) {
            $templateTitle = $this->container->getParameter('menu.search_title');
        }
        $this->get('pumukit_web_tv.breadcrumbs')->addList($blockedTag ? $blockedTag->getTitle() : $templateTitle?:'Multimedia object search', 'pumukit_webtv_search_multimediaobjects');

        // --- Get Tag Parent for Tag Fields ---
        $parentTag = $this->getParentTag();
        $parentTagOptional = $this->getOptionalParentTag();
        // --- END Get Tag Parent for Tag Fields ---

        // --- Get Variables ---
        $searchFound = $request->query->get('search');
        $tagsFound = $request->query->get('tags');
        $typeFound = $request->query->get('type');
        $durationFound = $request->query->get('duration');
        $startFound = $request->query->get('start');
        $endFound = $request->query->get('end');
        $yearFound = $request->query->get('year');
        $languageFound = $request->query->get('language');
        // --- END Get Variables --
        // --- Create QueryBuilder ---
        $mmobjRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $queryBuilder = $mmobjRepo->createStandardQueryBuilder();
        $queryBuilder = $this->searchQueryBuilder($queryBuilder, $searchFound);
        $queryBuilder = $this->typeQueryBuilder($queryBuilder, $typeFound);
        $queryBuilder = $this->durationQueryBuilder($queryBuilder, $durationFound);
        $queryBuilder = $this->dateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound);
        $queryBuilder = $this->languageQueryBuilder($queryBuilder, $languageFound);
        $queryBuilder = $this->tagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral);
        $queryBuilder = $queryBuilder->sort('record_date','desc');
        // --- END Create QueryBuilder ---
        // --- Execute QueryBuilder and get paged results ---
        $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));
        // --- Query to get existing languages ---
        $searchLanguages = $this->get('doctrine_mongodb')
        ->getRepository('PumukitSchemaBundle:MultimediaObject')
        ->createStandardQueryBuilder()
        ->distinct('tracks.language')
        ->getQuery()->execute();
        // --- Query to get oldest date ---
        $firstMmobj = $this->get('doctrine_mongodb')
        ->getRepository('PumukitSchemaBundle:MultimediaObject')
        ->createStandardQueryBuilder()->sort('record_date','asc')->limit(1)
        ->getQuery()->getSingleResult();
        $minRecordDate = $firstMmobj->getRecordDate()->format('m/d/Y');
        $maxRecordDate = date('m/d/Y');
        // --- Query to get years for the 'Year' select form. ---
        $searchYears = array();
        $maxYear = date('Y');
        $tempYear = $firstMmobj->getRecordDate()->format('Y');
        while($tempYear <= $maxYear) {
            $searchYears[] = $tempYear;
            $tempYear++;
        }
        // -- Init Number Cols for showing results ---
        $numberCols = $this->container->getParameter('columns_objs_search');

        // --- RETURN ---
        return array('type' => 'multimediaObject',
        'template_title' => $templateTitle,
        'objects' => $pagerfanta,
        'parent_tag' => $parentTag,
        'parent_tag_optional' => $parentTagOptional,
        'tags_found' => $tagsFound,
        'number_cols' => $numberCols,
        'languages' => $searchLanguages,
        'blocked_tag' => $blockedTag,
        'min_record_date' => $minRecordDate,
        'max_record_date' => $maxRecordDate,
        'search_years' => $searchYears );
    }

    private function createPager($objects, $page)
    {
        $limit = 10;
        if ($this->container->hasParameter('limit_objs_search')) {
            $limit = $this->container->getParameter('limit_objs_search');
        }
        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }


    private function getParentTag()
    {
        $tagRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
        $searchByTagCod = 'ITUNESU';
        if ($this->container->hasParameter('search.parent_tag.cod')) {
            $searchByTagCod = $this->container->getParameter('search.parent_tag.cod');
        }
        $parentTag = $tagRepo->findOneByCod($searchByTagCod);
        if (!isset($parentTag)) {
            throw new \Exception(sprintf('The parent Tag with COD:  \' %s  \' does not exist. Check if your tags are initialized and that you added the correct \'cod\' to parameters.yml (search.parent_tag.cod)', $searchByTagCod));
        }
        return $parentTag;
    }

    private function getOptionalParentTag()
    {
        $tagRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
        $parentTagOptional = null;
        if ($this->container->hasParameter('search.parent_tag_2.cod')) {
            $searchByTagCod2 = $this->container->getParameter('search.parent_tag_2.cod');
            $parentTagOptional = $tagRepo->findOneByCod($searchByTagCod2);
            if (!isset($parentTagOptional)) {
                throw new \Exception(sprintf('The parent Tag with COD:  \' %s  \' does not exist. Check if your tags are initialized and that you added the correct \'cod\' to parameters.yml (search.parent_tag.cod)', $searchByTagCod));
            }
        }
        return $parentTagOptional;
    }

    // ========= queryBuilder functions ==========

    private function searchQueryBuilder($queryBuilder, $searchFound)
    {
        if ($searchFound != '') {
            $queryBuilder->field('$text')->equals(array('$search' => $searchFound));
        }

        return $queryBuilder;
    }

    private function typeQueryBuilder($queryBuilder, $typeFound)
    {
        if ($typeFound != '') {
            $queryBuilder->field('tracks.only_audio')->equals($typeFound == 'Audio');
        }

        return $queryBuilder;
    }

    private function durationQueryBuilder($queryBuilder, $durationFound)
    {
        if ($durationFound != '') {
            if ($durationFound == '-5') {
                $queryBuilder->field('tracks.duration')->lte(300);
            }
            if ($durationFound == '-10') {
                $queryBuilder->field('tracks.duration')->lte(600);
            }
            if ($durationFound == '-30') {
                $queryBuilder->field('tracks.duration')->lte(1800);
            }
            if ($durationFound == '-60') {
                $queryBuilder->field('tracks.duration')->lte(3600);
            }
            if ($durationFound == '+60') {
                $queryBuilder->field('tracks.duration')->gt(3600);
            }
        }

        return $queryBuilder;
    }

    private function dateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound)
    {
        if( $yearFound ) {
            $start = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01',$yearFound));
            $end = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01',($yearFound)+1));
            $queryBuilder->field('record_date')->gte($start);
            $queryBuilder->field('record_date')->lt($end);
        }
        else {
            if ($startFound != '') {
                $start = \DateTime::createFromFormat('d/m/Y', $startFound);
                $queryBuilder->field('record_date')->gt($start);
            }
            if ($endFound != '') {
                $end = \DateTime::createFromFormat('d/m/Y', $endFound);
                $queryBuilder->field('record_date')->lt($end);
            }
        }

        return $queryBuilder;
    }

    private function languageQueryBuilder($queryBuilder, $languageFound)
    {
        if ($languageFound != '') {
            $queryBuilder->field('tracks.language')->equals($languageFound);
        }

        return $queryBuilder;
    }

    private function tagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral = false)
    {
        $tagRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
        if ($blockedTag !== null) {
            $tagsFound[] = $blockedTag->getCod();
        }
        if ($tagsFound !== null) {
            $tagsFound = array_values(array_diff($tagsFound, array('All', '')));
        }
        if (count($tagsFound) > 0) {
            $queryBuilder->field('tags.cod')->all($tagsFound);
        }

        if ($useTagAsGeneral && $blockedTag !== null) {
            $queryBuilder->field('tags.path')->notIn(array(new \MongoRegex('/'.preg_quote($blockedTag->getPath()).'.*\|/')));
        }

        return $queryBuilder;
    }
    // ========== END queryBuilder functions =========
}
