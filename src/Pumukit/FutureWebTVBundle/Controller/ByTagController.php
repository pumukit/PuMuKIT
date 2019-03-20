<?php

namespace Pumukit\FutureWebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Tag;

/**
 * Class ByTagController.
 */
class ByTagController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/multimediaobjects/tag/{tagCod}", name="pumukit_webtv_bytag_multimediaobjects", defaults={"tagCod": null})
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitFutureWebTVBundle:ByTag:template.html.twig")
     *
     * @param Tag     $tag
     * @param Request $request
     *
     * @return array
     */
    public function multimediaObjectsAction(Tag $tag, Request $request)
    {
        $numberCols = $this->container->getParameter('columns_objs_bytag');
        $limit = $this->container->getParameter('limit_objs_bytag');

        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        if ($request->get('useTagAsGeneral')) {
            //This should be included on SchemaBundle:MultimediaObjectRepository.
            $mmobjs = $repo->createBuilderWithGeneralTag($tag, ['record_date' => -1]);
            $title = $this->get('translator')->trans('General %title%', ['%title%' => $tag->getTitle()]);
            $this->updateBreadcrumbs($title, 'pumukit_webtv_bytag_multimediaobjects', ['tagCod' => $tag->getCod(), 'useTagAsGeneral' => true]);
        } else {
            $mmobjs = $repo->createBuilderWithTag($tag, ['record_date' => -1]);
            $this->updateBreadcrumbs($tag->getTitle(), 'pumukit_webtv_bytag_multimediaobjects', ['tagCod' => $tag->getCod()]);
            $title = $tag->getTitle();
        }

        $pagerfanta = $this->createPager($mmobjs, $request->query->get('page', 1), $limit);

        $title = $this->get('translator')->trans('Multimedia objects with tag: %title%', ['%title%' => $title]);

        return [
            'title' => $title,
            'objects' => $pagerfanta,
            'tag' => $tag,
            'objectByCol' => $numberCols,
            'show_info' => true,
            'show_description' => true,
        ];
    }

    /**
     * @Route("/series/tag/{tagCod}",  name="pumukit_webtv_bytag_series", defaults={"tagCod": null})
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitFutureWebTVBundle:ByTag:template.html.twig")
     *
     * @param Tag     $tag
     * @param Request $request
     *
     * @return array
     */
    public function seriesAction(Tag $tag, Request $request)
    {
        $numberCols = $this->container->getParameter('columns_objs_bytag');
        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $series = $repo->createBuilderWithTag($tag, ['public_date' => -1]);

        $pagerfanta = $this->createPager($series, $request->query->get('page', 1));
        $this->updateBreadcrumbs($tag->getTitle(), 'pumukit_webtv_bytag_series', ['tagCod' => $tag->getCod()]);

        $title = $tag->getTitle();
        $title = $this->get('translator')->trans('Series with tag: %title%', ['%title%' => $title]);

        return [
            'title' => $title,
            'objects' => $pagerfanta,
            'tag' => $tag,
            'objectByCol' => $numberCols,
            'show_info' => true,
        ];
    }

    /**
     * @param       $title
     * @param       $routeName
     * @param array $routeParameters
     */
    private function updateBreadcrumbs($title, $routeName, array $routeParameters = [])
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->add($title, $routeName, $routeParameters);
    }

    /**
     * @param     $objects
     * @param     $page
     * @param int $limit
     *
     * @return Pagerfanta
     */
    private function createPager($objects, $page, $limit = 10)
    {
        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($page);

        return $pager;
    }
}
