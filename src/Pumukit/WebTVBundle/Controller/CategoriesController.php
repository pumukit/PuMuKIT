<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Pumukit\WebTVBundle\Services\CategoriesService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CategoriesController extends AbstractController implements WebTVControllerInterface
{
    private $categoriesListGeneralTag;
    private $categoriesService;
    private $translator;
    private $breadcrumbService;
    private $menuCategoriesTitle;

    public function __construct(
        CategoriesService $categoriesService,
        TranslatorInterface $translator,
        BreadcrumbsService $breadcrumbService,
        $categoriesListGeneralTag,
        $menuCategoriesTitle
    ) {
        $this->categoriesListGeneralTag = $categoriesListGeneralTag;
        $this->categoriesService = $categoriesService;
        $this->translator = $translator;
        $this->breadcrumbService = $breadcrumbService;
        $this->menuCategoriesTitle = $menuCategoriesTitle;
    }

    /**
     * @Route("/categories", name="pumukit_webtv_categories_index")
     *
     * @Template("@PumukitWebTV/Categories/template.html.twig")
     */
    public function indexAction(Request $request)
    {
        $templateTitle = $this->translator->trans($this->menuCategoriesTitle);
        $this->breadcrumbService->addList($templateTitle ?: 'Videos by Category', 'pumukit_webtv_categories_index');

        [$elements, $groundsRootTitle] = $this->categoriesService->getCategoriesElements($request->get('provider'));

        return [
            'allGrounds' => $elements,
            'title' => $groundsRootTitle,
            'list_general_tags' => $this->categoriesListGeneralTag,
        ];
    }
}
