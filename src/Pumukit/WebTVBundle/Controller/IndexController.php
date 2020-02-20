<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController implements WebTVControllerInterface
{
    private $breadcrumbsService;
    private $menuShowStats;
    private $pumukitNewAdminAdvanceLiveEvent;

    public function __construct(BreadcrumbsService $breadcrumbsService, bool $menuShowStats, bool $pumukitNewAdminAdvanceLiveEvent)
    {
        $this->breadcrumbsService = $breadcrumbsService;
        $this->menuShowStats = $menuShowStats;
        $this->pumukitNewAdminAdvanceLiveEvent = $pumukitNewAdminAdvanceLiveEvent;
    }

    /**
     * @Route("/", name="pumukit_webtv_index_index")
     * @Template("@PumukitWebTV/Index/template.html.twig")
     */
    public function indexAction()
    {
        $this->breadcrumbsService->reset();

        return [
            'menu_stats' => $this->menuShowStats,
            'is_activated_advance_live_event' => $this->pumukitNewAdminAdvanceLiveEvent,
        ];
    }
}
