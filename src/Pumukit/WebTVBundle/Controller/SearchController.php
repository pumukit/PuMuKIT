<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\TagInterface;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Pumukit\WebTVBundle\Services\SearchService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchController extends AbstractController implements WebTVControllerInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var BreadcrumbsService */
    private $breadcrumbsService;

    /** @var SearchService */
    private $searchService;

    /** @var DocumentManager */
    private $documentManager;

    /** @var RequestStack */
    private $requestStack;

    /** @var PaginationService */
    private $paginationService;

    private $menuSearchTitle;
    private $columnsObjsSearch;
    private $pumukitNewAdminLicenses;
    private $limitObjsSearch;

    public function __construct(
        TranslatorInterface $translator,
        BreadcrumbsService $breadcrumbsService,
        SearchService $searchService,
        DocumentManager $documentManager,
        RequestStack $requestStack,
        PaginationService $paginationService,
        $menuSearchTitle,
        $columnsObjsSearch,
        $pumukitNewAdminLicenses,
        $limitObjsSearch
    ) {
        $this->translator = $translator;
        $this->breadcrumbsService = $breadcrumbsService;
        $this->searchService = $searchService;
        $this->documentManager = $documentManager;
        $this->requestStack = $requestStack;
        $this->paginationService = $paginationService;
        $this->menuSearchTitle = $menuSearchTitle;
        $this->columnsObjsSearch = $columnsObjsSearch;
        $this->pumukitNewAdminLicenses = $pumukitNewAdminLicenses;
        $this->limitObjsSearch = $limitObjsSearch;
    }

    /**
     * @Route("/searchseries", name="pumukit_webtv_search_series")
     * @Template("@PumukitWebTV/Search/template.html.twig")
     */
    public function seriesAction(Request $request)
    {
        $this->breadcrumbsService->addList($this->translator->trans('Series search'), 'pumukit_webtv_search_series');

        $searchYears = $this->searchService->getYears(SearchService::SERIES);

        $searchFound = $request->query->get('search');
        $startFound = $request->query->get('start');
        $endFound = $request->query->get('end');
        $yearFound = $request->query->get('year');

        $request = $this->requestStack->getCurrentRequest();
        $queryBuilder = $this->createSeriesQueryBuilder();
        $queryBuilder = $this->searchService->addValidSeriesQueryBuilder($queryBuilder);
        $queryBuilder = $this->searchService->addSearchQueryBuilder($queryBuilder, $request->getLocale(), $searchFound);
        $queryBuilder = $this->searchService->addDateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound, 'public_date');
        if ('' === $searchFound) {
            $queryBuilder = $queryBuilder->sort('public_date', 'desc');
        } else {
            $queryBuilder = $queryBuilder->sortMeta('score', 'textScore');
        }

        [$pager, $totalObjects] = $this->createPager($queryBuilder, $request->query->get('page', 1));

        return [
            'type' => 'series',
            'objects' => $pager,
            'search_years' => $searchYears,
            'objectByCol' => $this->columnsObjsSearch,
            'total_objects' => $totalObjects,
            'show_info' => true,
            'with_publicdate' => true,
            'class' => 'searchseries',
        ];
    }

    /**
     * @Route("/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}", defaults={"tagCod": null, "useTagAsGeneral": false}, name="pumukit_webtv_search_multimediaobjects")
     * @ParamConverter("blockedTag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("@PumukitWebTV/Search/template.html.twig")
     */
    public function multimediaObjectsAction(Request $request, TagInterface $blockedTag = null, bool $useTagAsGeneral = false)
    {
        $templateTitle = $this->menuSearchTitle ?? 'Multimedia objects search';
        $this->breadcrumbsService->addList($blockedTag ? $blockedTag->getTitle() : $this->translator->trans($templateTitle), 'pumukit_webtv_search_multimediaobjects');

        [$parentTag, $parentTagOptional] = $this->searchService->getSearchTags();
        $searchLanguages = $this->searchService->getLanguages();
        $searchYears = $this->searchService->getYears(SearchService::MULTIMEDIA_OBJECT);

        $searchFound = $request->query->get('search');
        $tagsFound = $request->query->get('tags');
        $typeFound = $request->query->get('type');
        $durationFound = $request->query->get('duration');
        $startFound = $request->query->get('start');
        $endFound = $request->query->get('end');
        $yearFound = $request->query->get('year');
        $languageFound = $request->query->get('language');
        $license = $request->query->get('license');

        $request = $this->requestStack->getCurrentRequest();
        $queryBuilder = $this->createMultimediaObjectQueryBuilder();
        $queryBuilder = $this->searchService->addSearchQueryBuilder($queryBuilder, $request->getLocale(), $searchFound);
        $queryBuilder = $this->searchService->addTypeQueryBuilder($queryBuilder, $typeFound);
        $queryBuilder = $this->searchService->addDurationQueryBuilder($queryBuilder, $durationFound);
        $queryBuilder = $this->searchService->addDateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound);
        $queryBuilder = $this->searchService->addLanguageQueryBuilder($queryBuilder, $languageFound);
        $queryBuilder = $this->searchService->addTagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral);
        $queryBuilder = $this->searchService->addLicenseQueryBuilder($queryBuilder, $license);

        $templateListGrouped = false;
        if ('' === $searchFound) {
            $queryBuilder = $queryBuilder->sort('record_date', 'desc');
            $templateListGrouped = true;
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
            'template_list_grouped' => $templateListGrouped,
            'objects' => $pager,
            'parent_tag' => $parentTag,
            'parent_tag_optional' => $parentTagOptional,
            'tags_found' => $tagsFound,
            'objectByCol' => $this->columnsObjsSearch,
            'licenses' => $this->pumukitNewAdminLicenses,
            'languages' => $searchLanguages,
            'blocked_tag' => $blockedTag,
            'search_years' => $searchYears,
            'total_objects' => $totalObjects,
            'class' => 'searchmultimediaobjects',
            'show_info' => true,
            'with_publicdate' => true,
        ];
    }

    protected function createPager($objects, $page)
    {
        $pager = $this->paginationService->createDoctrineODMMongoDBAdapter($objects, $page, $this->limitObjsSearch);

        $pager->getCurrentPageResults();
        $totalObjects = $pager->getNbResults();

        return [
            $pager,
            $totalObjects,
        ];
    }

    protected function createSeriesQueryBuilder()
    {
        return $this->documentManager->getRepository(Series::class)->createQueryBuilder();
    }

    protected function createMultimediaObjectQueryBuilder()
    {
        return $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();
    }
}
