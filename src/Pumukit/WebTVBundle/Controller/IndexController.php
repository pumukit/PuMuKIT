<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class IndexController.
 */
class IndexController extends AbstractController implements WebTVControllerInterface
{
    private $breadcrumbsService;
    private $showStats;

    public function __construct(BreadcrumbsService $breadcrumbsService, bool $showStats)
    {
        $this->breadcrumbsService  = $breadcrumbsService;
        $this->showStats = $showStats;
    }

    /**
     * @Route("/", name="pumukit_webtv_index_index")
     * @Template("PumukitWebTVBundle:Index:template.html.twig")
     */
    public function indexAction()
    {
        $this->breadcrumbsService->reset();

        return [
            'menu_stats' => $this->showStats,
        ];
    }
}
