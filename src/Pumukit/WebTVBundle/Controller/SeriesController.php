<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SeriesController extends AbstractController implements WebTVControllerInterface
{
    /** @var DocumentManager */
    private $documentManager;

    /** @var BreadcrumbsService */
    private $breadcrumbsService;

    /** @var PaginationService */
    private $paginationService;
    private $limitObjsSeries;

    public function __construct(
        DocumentManager $documentManager,
        BreadcrumbsService $breadcrumbsService,
        PaginationService $paginationService,
        $limitObjsSeries
    ) {
        $this->documentManager = $documentManager;
        $this->breadcrumbsService = $breadcrumbsService;
        $this->paginationService = $paginationService;
        $this->limitObjsSeries = $limitObjsSeries;
    }

    /**
     * @Route("/series/{id}", name="pumukit_webtv_series_index")
     *
     * @Template("@PumukitWebTV/Series/template.html.twig")
     */
    public function indexAction(Request $request, Series $series)
    {
        $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);

        $objects = $mmobjRepo->createBuilderWithSeriesAndStatus($series, [MultimediaObject::STATUS_PUBLISHED], ['rank' => 1]);

        $pager = $this->createPager($objects, $request->query->get('page', 1));

        $this->updateBreadcrumbs($series);

        return [
            'series' => $series,
            'multimediaObjects' => $pager,
        ];
    }

    /**
     * @Route("/series/magic/{secret}", name="pumukit_webtv_series_magicindex", defaults={"show_hide"=true, "broadcast"=false})
     *
     * @Template("@PumukitWebTV/Series/template.html.twig")
     */
    public function magicIndexAction(Request $request, Series $series)
    {
        $request->attributes->set('noindex', true);
        $objects = $this->documentManager->getRepository(MultimediaObject::class)->createBuilderWithSeries(
            $series,
            ['rank' => 1]
        );

        $pager = $this->createPager($objects, $request->query->get('page', 1));

        $this->updateBreadcrumbs($series);

        return [
            'series' => $series,
            'multimediaObjects' => $pager,
            'magic_url' => true,
        ];
    }

    private function updateBreadcrumbs(Series $series)
    {
        $this->breadcrumbsService->addSeries($series);
    }

    private function createPager($objects, $page)
    {
        return $this->paginationService->createDoctrineODMMongoDBAdapter($objects, $page, $this->limitObjsSeries);
    }
}
