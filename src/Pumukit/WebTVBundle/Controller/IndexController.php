<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController implements WebTVControllerInterface
{
    protected $breadcrumbsService;
    protected $menuShowStats;

    public function __construct(BreadcrumbsService $breadcrumbsService, bool $menuShowStats)
    {
        $this->breadcrumbsService = $breadcrumbsService;
        $this->menuShowStats = $menuShowStats;
    }

    /**
     * @Route("/", name="pumukit_webtv_index_index")
     */
    public function indexAction(): Response
    {
        $this->breadcrumbsService->reset();

        return $this->render('@PumukitWebTV/Index/template.html.twig', [
            'menu_stats' => $this->menuShowStats,
        ]);
    }
}
