<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ModulesController.
 */
class ModulesController extends Controller implements WebTVController
{
    /**
     * @Template("PumukitWebTVBundle:Modules:widget_media.html.twig")
     *
     * @return array
     */
    public function mostViewedAction()
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $limit = $this->container->getParameter('limit_objs_mostviewed');
        $showLastMonth = $this->container->getParameter('show_mostviewed_lastmonth');
        $translator = $this->get('translator');

        if ($showLastMonth) {
            $objects = $this->get('pumukit_stats.stats')->getMostViewedUsingFilters(30, $limit);
            $title = $translator->trans('Most viewed on the last month');
        } else {
            $objects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findStandardBy(array(), array('numview' => -1), $limit, 0);
            $title = $translator->trans('Most viewed');
        }

        return array(
            'objects' => $objects,
            'objectByCol' => $this->container->getParameter('mostviewed.objects_by_col'),
            'title' => $title,
            'class' => 'mostviewed',
            'show_info' => true,
            'show_more' => false,
        );
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_media.html.twig")
     *
     * @return array
     */
    public function recentlyAddedAction()
    {
        $translator = $this->get('translator');
        $title = $translator->trans('Recently added');

        $limit = $this->container->getParameter('limit_objs_recentlyadded');
        $showPudenew = false;

        $last = $this->get('pumukitschema.announce')->getLast($limit, $showPudenew);

        return array(
            'objects' => $last,
            'objectByCol' =>  $this->container->getParameter('recentlyadded.objects_by_col'),
            'title' => $title,
            'class' => 'recently',
            'show_info' => true,
            'show_more' => false,
        );
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_media.html.twig")
     *
     * @return array
     */
    public function highlightAction()
    {
        $translator = $this->get('translator');
        $title = $translator->trans('Hightlight');

        $limit = $this->container->getParameter('limit_objs_hightlight');
        $showPudenew = $this->container->getParameter('show_latest_with_pudenew');

        $last = $this->get('pumukitschema.announce')->getLast($limit, $showPudenew);

        return array(
            'objects' => $last,
            'objectByCol' => $this->container->getParameter('hightlight.objects_by_col'),
            'class' => 'highlight',
            'title' => $title,
            'show_info' => false,
            'show_more' => false,
        );
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_stats.html.twig")
     *
     * @return array
     */
    public function statsAction()
    {
        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:series');

        $counts = array(
            'series' => $seriesRepo->countPublic(),
            'mms' => $mmRepo->count(),
            'hours' => $mmRepo->countDuration(),
        );

        return array('counts' => $counts);
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_breadcrumb.html.twig")
     */
    public function breadcrumbsAction()
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');

        return array('breadcrumbs' => $breadcrumbs->getBreadcrumbs());
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_language.html.twig")
     */
    public function languageAction()
    {
        $array_locales = $this->container->getParameter('pumukit2.locales');
        if (count($array_locales) <= 1) {
            return new Response('');
        }

        return array('languages' => $array_locales);
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_categories.html.twig")
     *
     * @param Request $request
     * @param         $title
     * @param         $class
     * @param         $categories
     * @param int     $cols
     *
     * @return array
     */
    public function categoriesAction(Request $request, $title, $class, $categories, $cols = 6)
    {
        if (!$categories) {
            throw new NotFoundHttpException('Categories not found');
        }

        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $tags = $dm->createQueryBuilder('PumukitSchemaBundle:Tag')
            ->field('cod')->in($categories)
            ->field('display')->equals(true)
            ->sort("title.".$request->getLocale(),1)
            ->getQuery()
            ->execute();

        return array(
            'objectByCol' => $cols,
            'objects' => $tags,
            'objectsData' => $categories,
            'title' => $title,
            'class' => $class,
        );
    }
}
