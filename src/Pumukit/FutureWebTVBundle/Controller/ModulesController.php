<?php

namespace Pumukit\FutureWebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ModulesController.
 */
class ModulesController extends Controller implements WebTVControllerInterface
{
    /**
     * @Template("PumukitFutureWebTVBundle:Modules:widget_media.html.twig")
     *
     * @param string $design
     *
     * @return array
     */
    public function mostViewedAction($design = 'horizontal')
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $limit = $this->container->getParameter('limit_objs_mostviewed');
        $showLastMonth = $this->container->getParameter('show_mostviewed_lastmonth');
        $translator = $this->get('translator');

        if ($showLastMonth) {
            $objects = $this->get('pumukit_stats.stats')->getMostViewedUsingFilters(30, $limit);
            $title = $translator->trans('Most viewed on the last month');
        } else {
            $objects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findStandardBy([], ['numview' => -1], $limit, 0);
            $title = $translator->trans('Most viewed');
        }

        return [
            'design' => $design,
            'objects' => $objects,
            'objectByCol' => $this->container->getParameter('mostviewed.objects_by_col'),
            'title' => $title,
            'class' => 'mostviewed',
            'show_info' => true,
            'show_more' => false,
        ];
    }

    /**
     * @Template("PumukitFutureWebTVBundle:Modules:widget_media.html.twig")
     *
     * @param string $design
     * @param bool   $showPudeNew
     *
     * @return array
     */
    public function recentlyAddedAction($design = 'horizontal', $showPudeNew = false)
    {
        $translator = $this->get('translator');
        $title = $translator->trans('Recently added');

        $limit = $this->container->getParameter('limit_objs_recentlyadded');

        $last = $this->get('pumukitschema.announce')->getLast($limit, $showPudeNew);

        return [
            'design' => $design,
            'objects' => $last,
            'objectByCol' => $this->container->getParameter('recentlyadded.objects_by_col'),
            'title' => $title,
            'class' => 'recently',
            'show_info' => true,
            'show_more' => false,
        ];
    }

    /**
     * @Template("PumukitFutureWebTVBundle:Modules:widget_media.html.twig")
     *
     * @param bool $showPudeNew
     *
     * @return array
     */
    public function highlightAction($showPudeNew = true)
    {
        $translator = $this->get('translator');
        $title = $translator->trans('Hightlight');

        $limit = $this->container->getParameter('limit_objs_hightlight');

        $last = $this->get('pumukitschema.announce')->getLast($limit, $showPudeNew);

        return [
            'objects' => $last,
            'objectByCol' => $this->container->getParameter('hightlight.objects_by_col'),
            'class' => 'highlight',
            'title' => $title,
            'show_info' => false,
            'show_more' => false,
        ];
    }

    /**
     * @Template("PumukitFutureWebTVBundle:Modules:widget_stats.html.twig")
     *
     * @return array
     */
    public function statsAction()
    {
        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:series');

        $counts = [
            'series' => $seriesRepo->countPublic(),
            'mms' => $mmRepo->count(),
            'hours' => $mmRepo->countDuration(),
        ];

        return ['counts' => $counts];
    }

    /**
     * @Template("PumukitFutureWebTVBundle:Modules:widget_breadcrumb.html.twig")
     */
    public function breadcrumbsAction()
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');

        return ['breadcrumbs' => $breadcrumbs->getBreadcrumbs()];
    }

    /**
     * @Template("PumukitFutureWebTVBundle:Modules:widget_language.html.twig")
     */
    public function languageAction()
    {
        $array_locales = $this->container->getParameter('pumukit2.locales');
        if (count($array_locales) <= 1) {
            return new Response('');
        }

        return ['languages' => $array_locales];
    }

    /**
     * @Template("PumukitFutureWebTVBundle:Modules:widget_categories.html.twig")
     *
     * @param Request $request
     * @param         $title
     * @param         $class
     * @param array   $categories
     * @param int     $cols
     *
     * @return array
     */
    public function categoriesAction(Request $request, $title, $class, array $categories, $cols = 6)
    {
        if (!$categories) {
            throw new NotFoundHttpException('Categories not found');
        }

        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $tags = $dm->createQueryBuilder('PumukitSchemaBundle:Tag')
            ->field('cod')->in($categories)
            ->field('display')->equals(true)
            ->sort('title.'.$request->getLocale(), 1)
            ->getQuery()
            ->execute();

        return [
            'objectByCol' => $cols,
            'objects' => $tags,
            'objectsData' => $categories,
            'title' => $title,
            'class' => $class,
        ];
    }

    /**
     * This module represents old categories block of PuMuKIT. Remember fix responsive design ( depends of height of images ).
     *
     * @Template("PumukitFutureWebTVBundle:Modules:widget_block_categories.html.twig")
     *
     * @return array
     */
    public function legacyCategoriesAction()
    {
        return [];
    }

    public static $menuResponse = null;
    private $menuTemplate = 'PumukitFutureWebTVBundle:Modules:widget_menu.html.twig';

    /**
     * This module represents old menu block of PuMuKIT ( vertical menu ). This design is just bootstrap panel example.
     *
     * @Template("PumukitFutureWebTVBundle:Modules:widget_menu.html.twig")
     *
     * @return null|Response
     *
     * @throws \Exception
     */
    public function legacyMenuAction()
    {
        if (self::$menuResponse) {
            return self::$menuResponse;
        }
        $params = $this->getLegacyMenuElements();
        self::$menuResponse = $this->render($this->menuTemplate, $params);

        return self::$menuResponse;
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    private function getLegacyMenuElements()
    {
        $menuService = $this->get('pumukit_web_tv.menu_service');
        list($events, $channels, $liveEventTypeSession) = $menuService->getMenuEventsElement();
        $selected = $this->get('request_stack')->getMasterRequest()->get('_route');
        $homeTitle = $this->container->getParameter('menu.home_title');
        $announcesTitle = $this->container->getParameter('menu.announces_title');
        $searchTitle = $this->container->getParameter('menu.search_title');
        $catalogueTitle = $this->container->getParameter('menu.mediateca_title');
        $categoriesTitle = $this->container->getParameter('menu.categories_title');

        return [
            'events' => $events,
            'channels' => $channels,
            'type' => $liveEventTypeSession,
            'menu_selected' => $selected,
            'home_title' => $homeTitle,
            'announces_title' => $announcesTitle,
            'search_title' => $searchTitle,
            'catalogue_title' => $catalogueTitle,
            'categories_title' => $categoriesTitle,
        ];
    }
}
