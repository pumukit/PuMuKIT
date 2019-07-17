<?php

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\ODM\MongoDB\Query\Builder;
use Pagerfanta\Pagerfanta;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\WebTVBundle\Services\SearchService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SearchController.
 */
class SearchController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/searchseries", name="pumukit_webtv_search_series")
     * @Template("PumukitWebTVBundle:Search:template.html.twig")
     *
     * @param Request $request
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @throws \MongoException
     *
     * @return array
     */
    public function seriesAction(Request $request)
    {
        // Setting breadcrumb
        $templateTitle = 'Series search';
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle, 'pumukit_webtv_search_series');

        // Get selecting data form
        $searchYears = $this->get('pumukit_web_tv.search_service')->getYears(SearchService::SERIES);
        $numberCols = $this->container->getParameter('columns_objs_search');

        // Generate QueryBuilder search
        $searchFound = $request->query->get('search');
        $startFound = $request->query->get('start');
        $endFound = $request->query->get('end');
        $yearFound = $request->query->get('year');

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $queryBuilder = $this->createSeriesQueryBuilder();
        $queryBuilder = $this->get('pumukit_web_tv.search_service')->addValidSeriesQueryBuilder($queryBuilder);
        $queryBuilder = $this->get('pumukit_web_tv.search_service')->addSearchQueryBuilder($queryBuilder, $request->getLocale(), $searchFound);
        $queryBuilder = $this->get('pumukit_web_tv.search_service')->addDateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound, 'public_date');
        if ('' == $searchFound) {
            $queryBuilder = $queryBuilder->sort('public_date', 'desc');
        } else {
            $queryBuilder = $queryBuilder->sortMeta('score', 'textScore');
        }

        // --- END Create QueryBuilder ---

        // --- Execute QueryBuilder and get paged results ---
        [$pager, $totalObjects] = $this->createPager($queryBuilder, $request->query->get('page', 1));

        // --- RETURN ---
        return [
            'type' => 'series',
            'objects' => $pager,
            'search_years' => $searchYears,
            'objectByCol' => $numberCols,
            'total_objects' => $totalObjects,
            'show_info' => true,
            'with_publicdate' => true,
            'class' => 'searchseries',
        ];
    }

    /**
     * @Route("/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}", defaults={"tagCod": null, "useTagAsGeneral": false}, name="pumukit_webtv_search_multimediaobjects")
     * @ParamConverter("blockedTag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitWebTVBundle:Search:template.html.twig")
     *
     * @param Request  $request
     * @param null|Tag $blockedTag
     * @param bool     $useTagAsGeneral
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @throws \MongoException
     *
     * @return array
     */
    public function multimediaObjectsAction(Request $request, Tag $blockedTag = null, $useTagAsGeneral = false)
    {
        // Setting breadcrumb
        $templateTitle = $this->container->getParameter('menu.search_title') ?: 'Multimedia objects search';
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($blockedTag ? $blockedTag->getTitle() : $templateTitle, 'pumukit_webtv_search_multimediaobjects');

        // Get selecting data form
        [$parentTag, $parentTagOptional] = $this->get('pumukit_web_tv.search_service')->getSearchTags();
        $searchLanguages = $this->get('pumukit_web_tv.search_service')->getLanguages();
        $searchYears = $this->get('pumukit_web_tv.search_service')->getYears(SearchService::MULTIMEDIA_OBJECT);
        $numberCols = $this->container->getParameter('columns_objs_search');
        $licenses = $this->container->getParameter('pumukit_new_admin.licenses');

        // Generate QueryBuilder search
        $searchFound = $request->query->get('search');
        $tagsFound = $request->query->get('tags');
        $typeFound = $request->query->get('type');
        $durationFound = $request->query->get('duration');
        $startFound = $request->query->get('start');
        $endFound = $request->query->get('end');
        $yearFound = $request->query->get('year');
        $languageFound = $request->query->get('language');
        $license = $request->query->get('license');

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $queryBuilder = $this->createMultimediaObjectQueryBuilder();
        $queryBuilder = $this->get('pumukit_web_tv.search_service')->addSearchQueryBuilder($queryBuilder, $request->getLocale(), $searchFound);
        $queryBuilder = $this->get('pumukit_web_tv.search_service')->addTypeQueryBuilder($queryBuilder, $typeFound);
        $queryBuilder = $this->get('pumukit_web_tv.search_service')->addDurationQueryBuilder($queryBuilder, $durationFound);
        $queryBuilder = $this->get('pumukit_web_tv.search_service')->addDateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound);
        $queryBuilder = $this->get('pumukit_web_tv.search_service')->addLanguageQueryBuilder($queryBuilder, $languageFound);
        $queryBuilder = $this->get('pumukit_web_tv.search_service')->addTagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral);
        $queryBuilder = $this->get('pumukit_web_tv.search_service')->addLicenseQueryBuilder($queryBuilder, $license);

        if ('' == $searchFound) {
            $queryBuilder = $queryBuilder->sort('record_date', 'desc');
        } else {
            $queryBuilder = $queryBuilder->sortMeta('score', 'textScore');
        }

        if ($request->attributes->get('only_public')) {
            $queryBuilder->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC);
        }

        [$pager, $totalObjects] = $this->createPager($queryBuilder, $request->query->get('page', 1));

        return [
            'type' => 'multimediaObject',
            'template_title' => $templateTitle,
            'objects' => $pager,
            'parent_tag' => $parentTag,
            'parent_tag_optional' => $parentTagOptional,
            'tags_found' => $tagsFound,
            'objectByCol' => $numberCols,
            'licenses' => $licenses,
            'languages' => $searchLanguages,
            'blocked_tag' => $blockedTag,
            'search_years' => $searchYears,
            'total_objects' => $totalObjects,
            'class' => 'searchmultimediaobjects',
            'show_info' => true,
            'with_publicdate' => true,
        ];
    }

    /**
     * @param $objects
     * @param $page
     *
     * @throws \Exception
     *
     * @return mixed|Pagerfanta
     */
    protected function createPager($objects, $page)
    {
        $limit = $this->container->getParameter('limit_objs_search');
        $pager = $this->get('pumukit_web_tv.pagination_service')->createDoctrineODMMongoDBAdapter($objects, $page, $limit);

        $pager->getCurrentPageResults(); // TTK-17149 force the complete search query to avoid a new query to count
        $totalObjects = $pager->getNbResults();

        return [
            $pager,
            $totalObjects,
        ];
    }

    /**
     * @return Builder
     */
    protected function createSeriesQueryBuilder()
    {
        $repo = $this->get('doctrine_mongodb')->getRepository(Series::class);

        return $repo->createQueryBuilder();
    }

    /**
     * @return Builder
     */
    protected function createMultimediaObjectQueryBuilder()
    {
        $repo = $this->get('doctrine_mongodb')->getRepository(MultimediaObject::class);

        return $repo->createStandardQueryBuilder();
    }
}
