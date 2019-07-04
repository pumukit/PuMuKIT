<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CategoriesController.
 */
class CategoriesController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/categories", name="pumukit_webtv_categories_index")
     * @Template("PumukitWebTVBundle:Categories:template.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function indexAction(Request $request)
    {
        $listGeneralParam = $this->getCategoriesParameters();

        $templateTitle = $this->container->getParameter('menu.categories_title');
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle ?: 'Videos by Category', 'pumukit_webtv_categories_index');

        [$elements, $groundsRootTitle] = $this->get('pumukit_web_tv.categories_service')->getCategoriesElements($request->get('provider'));

        return [
            'allGrounds' => $elements,
            'title' => $groundsRootTitle,
            'list_general_tags' => $listGeneralParam,
        ];
    }

    /**
     * @return array
     */
    private function getCategoriesParameters()
    {
        return [
            $this->container->getParameter('categories.list_general_tags'),
        ];
    }
}
