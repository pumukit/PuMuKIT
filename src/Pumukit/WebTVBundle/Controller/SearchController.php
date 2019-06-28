<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\WebTVBundle\Services\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Utils\Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;

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
     * @return array
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function seriesAction(Request $request)
    {
        $templateTitle = 'Series search';
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle, 'pumukit_webtv_search_series');

        // --- Get Variables ---
        $searchFound = $request->query->get('search');
        $startFound = $request->query->get('start');
        $endFound = $request->query->get('end');
        $yearFound = $request->query->get('year');
        // --- END Get Variables --
        // --- Get valid series ids ---
        $validSeries = $this->get('doctrine_mongodb')->getRepository(MultimediaObject::class)
            ->createStandardQueryBuilder()
            ->distinct('series')
            ->getQuery()
            ->execute()->toArray();
        // --- END Get valid series ids ---
        // --- Create QueryBuilder ---
        $queryBuilder = $this->createSeriesQueryBuilder();
        $queryBuilder = $queryBuilder->field('_id')->in($validSeries);
        $queryBuilder = $this->searchQueryBuilder($queryBuilder, $searchFound);
        $queryBuilder = $this->dateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound, 'public_date');
        if ('' == $searchFound) {
            $queryBuilder = $queryBuilder->sort('public_date', 'desc');
        } else {
            $queryBuilder = $queryBuilder->sortMeta('score', 'textScore');
        }

        // --- END Create QueryBuilder ---

        // --- Execute QueryBuilder and get paged results ---
        $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));
        $pagerfanta->getCurrentPageResults(); // TTK-17149 force the complete search query to avoid a new query to count
        $totalObjects = $pagerfanta->getNbResults();

        // -- Get years array --
        $searchYears = $this->get('pumukit_web_tv.search_service')->getYears(SearchService::SERIES);

        // -- Init Number Cols for showing results ---
        $numberCols = $this->container->getParameter('columns_objs_search');

        // --- RETURN ---
        return [
            'type' => 'series',
            'objects' => $pagerfanta,
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
     */
    public function multimediaObjectsAction(Request $request, Tag $blockedTag = null, $useTagAsGeneral = false)
    {
        //Add translated title to breadcrumbs.
        $templateTitle = $this->container->getParameter('menu.search_title') ?: 'Multimedia objects search';
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($blockedTag ? $blockedTag->getTitle() : $templateTitle,'pumukit_webtv_search_multimediaobjects');

        [$parentTag, $parentTagOptional] = $this->get('pumukit_web_tv.search_service')->getSearchTags();

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
        $queryBuilder = $this->createMultimediaObjectQueryBuilder();
        $queryBuilder = $this->searchQueryBuilder($queryBuilder, $searchFound);
        $queryBuilder = $this->typeQueryBuilder($queryBuilder, $typeFound);
        $queryBuilder = $this->durationQueryBuilder($queryBuilder, $durationFound);
        $queryBuilder = $this->dateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound);
        $queryBuilder = $this->languageQueryBuilder($queryBuilder, $languageFound);
        $queryBuilder = $this->tagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral);
        if ('' == $searchFound) {
            $queryBuilder = $queryBuilder->sort('record_date', 'desc');
        } else {
            $queryBuilder = $queryBuilder->sortMeta('score', 'textScore');
        }

        if ($request->attributes->get('only_public')) {
            $queryBuilder->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC);
        }
        // --- END Create QueryBuilder ---

        // --- Execute QueryBuilder and get paged results ---
        $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));
        $pagerfanta->getCurrentPageResults(); // TTK-17149 force the complete search query to avoid a new query to count
        $totalObjects = $pagerfanta->getNbResults();

        // --- Query to get existing languages ---
        $searchLanguages = $this->get('doctrine_mongodb')
            ->getRepository(MultimediaObject::class)
            ->createStandardQueryBuilder()
            ->distinct('tracks.language')
            ->getQuery()->execute();
        // --- Get years array ---
        $searchYears = $this->get('pumukit_web_tv.search_service')->getYears(SearchService::MULTIMEDIA_OBJECT);

        // -- Init Number Cols for showing results ---
        $numberCols = $this->container->getParameter('columns_objs_search');

        // --- RETURN ---
        return [
            'type' => 'multimediaObject',
            'template_title' => $templateTitle,
            'objects' => $pagerfanta,
            'parent_tag' => $parentTag,
            'parent_tag_optional' => $parentTagOptional,
            'tags_found' => $tagsFound,
            'objectByCol' => $numberCols,
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
     * @return mixed|Pagerfanta
     * @throws \Exception
     */
    protected function createPager($objects, $page)
    {
        $limit = $this->container->getParameter('limit_objs_search');
        $pager = $this->get('pumukit_web_tv.pagination_service')->createDoctrineODMMongoDBAdapter($objects, $page, $limit);
        return $pager;
    }

    /**
     * @param $queryBuilder
     * @param $searchFound
     *
     * @return mixed
     */
    protected function searchQueryBuilder($queryBuilder, $searchFound)
    {
        $searchFound = trim($searchFound);
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ((false !== strpos($searchFound, '*')) && (false === strpos($searchFound, ' '))) {
            $searchFound = str_replace('*', '.*', $searchFound);
            $mRegex = new \MongoRegex("/$searchFound/i");
            $queryBuilder->addOr($queryBuilder->expr()->field('title.'.$request->getLocale())->equals($mRegex));
            $queryBuilder->addOr($queryBuilder->expr()->field('people.people.name')->equals($mRegex));
        } elseif ('' != $searchFound) {
            $queryBuilder->field('$text')->equals([
                '$search' => TextIndexUtils::cleanTextIndex($searchFound),
                '$language' => TextIndexUtils::getCloseLanguage($request->getLocale()),
            ]);
        }

        return $queryBuilder;
    }

    /**
     * @param $queryBuilder
     * @param $typeFound
     *
     * @return mixed
     */
    protected function typeQueryBuilder($queryBuilder, $typeFound)
    {
        if ('' != $typeFound) {
            $queryBuilder->field('type')->equals(
                ('audio' == $typeFound) ? Multimediaobject::TYPE_AUDIO : Multimediaobject::TYPE_VIDEO
            );
        }

        return $queryBuilder;
    }

    /**
     * @param $queryBuilder
     * @param $durationFound
     *
     * @return mixed
     */
    protected function durationQueryBuilder($queryBuilder, $durationFound)
    {
        if ('' != $durationFound) {
            if ('-5' == $durationFound) {
                $queryBuilder->field('tracks.duration')->lte(300);
            }
            if ('-10' == $durationFound) {
                $queryBuilder->field('tracks.duration')->lte(600);
            }
            if ('-30' == $durationFound) {
                $queryBuilder->field('tracks.duration')->lte(1800);
            }
            if ('-60' == $durationFound) {
                $queryBuilder->field('tracks.duration')->lte(3600);
            }
            if ('+60' == $durationFound) {
                $queryBuilder->field('tracks.duration')->gt(3600);
            }
        }

        return $queryBuilder;
    }

    /**
     * @param        $queryBuilder
     * @param        $startFound
     * @param        $endFound
     * @param        $yearFound
     * @param string $dateField
     *
     * @return mixed
     */
    protected function dateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound, $dateField = 'record_date')
    {
        if (null !== $yearFound && '' !== $yearFound) {
            $start = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:00', $yearFound));
            $end = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:00', ($yearFound) + 1));
            $queryBuilder->field($dateField)->gte($start);
            $queryBuilder->field($dateField)->lt($end);
        } else {
            if ('' != $startFound) {
                $start = \DateTime::createFromFormat('!Y-m-d', $startFound);
                $queryBuilder->field($dateField)->gt($start);
            }
            if ('' != $endFound) {
                $end = \DateTime::createFromFormat('!Y-m-d', $endFound);
                $end->modify('+1 day');
                $queryBuilder->field($dateField)->lt($end);
            }
        }

        return $queryBuilder;
    }

    /**
     * @param $queryBuilder
     * @param $languageFound
     *
     * @return mixed
     */
    protected function languageQueryBuilder($queryBuilder, $languageFound)
    {
        if ('' != $languageFound) {
            $queryBuilder->field('tracks.language')->equals($languageFound);
        }

        return $queryBuilder;
    }

    /**
     * @param      $queryBuilder
     * @param      $tagsFound
     * @param      $blockedTag
     * @param bool $useTagAsGeneral
     *
     * @return mixed
     */
    protected function tagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral = false)
    {
        if (null !== $blockedTag) {
            $tagsFound[] = $blockedTag->getCod();
        }
        if (null !== $tagsFound) {
            $tagsFound = array_values(array_diff($tagsFound, ['All', '']));
        }

        if ($tagsFound && count($tagsFound) > 0) {
            $queryBuilder->field('tags.cod')->all($tagsFound);
        }

        if ($useTagAsGeneral && null !== $blockedTag) {
            $queryBuilder->field('tags.path')->notIn([new \MongoRegex('/'.preg_quote($blockedTag->getPath()).'.*\|/')]);
        }

        return $queryBuilder;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function createSeriesQueryBuilder()
    {
        $repo = $this->get('doctrine_mongodb')->getRepository(Series::class);

        return $repo->createQueryBuilder();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function createMultimediaObjectQueryBuilder()
    {
        $repo = $this->get('doctrine_mongodb')->getRepository(MultimediaObject::class);

        return $repo->createStandardQueryBuilder();
    }
}
