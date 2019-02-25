<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

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
            'objectByCol' => 4,
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
            'objectByCol' => 4,
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
            'objectByCol' => 3,
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
}
