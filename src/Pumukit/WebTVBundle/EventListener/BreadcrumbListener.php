<?php

namespace Pumukit\WebTVBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;

class BreadcrumbListener
{
    private $session;
    private $router;
    private $allTitle;
    private $allRoute;
    private $homeTitle;
    private $breadcrumbs;
    private $translator;
    private $container;
    private $dm;

    private $allowRoutes = array(
        'pumukit_webtv_bytag_series',
        'pumukit_webtv_bytag_multimediaobjects',
        'pumukit_webtv_multimediaobject_index',
        'pumukit_webtv_series_index',
    );

    private $byTagRoutes = array(
        'pumukit_webtv_bytag_series',
        'pumukit_webtv_bytag_multimediaobjects',
    );

    private $needReference = array(
        'pumukit_webtv_multimediaobject_index',
        'pumukit_webtv_series_index',
    );

    private $seriesRoutes = array(
        'pumukit_webtv_bytag_series',
        'pumukit_webtv_series_magicindex',
    );

    private $multimediaObjectRoutes = array(
        'pumukit_webtv_bytag_multimediaobjects',
        'pumukit_webtv_multimediaobject_magicindex',
    );

    private $firstRoutes = array(
        'pumukit_webtv_announces_latestuploads' => array(
            'title' => 'menu.announces_title',
            'link' => 'pumukit_webtv_announces_latestuploads',
        ),
        'pumukit_webtv_medialibrary_index' => array(
            'title' => 'menu.mediateca_title',
            'link' => 'pumukit_webtv_medialibrary_index',
        ),
        'pumukit_webtv_search_multimediaobjects' => array(
            'title' => 'menu.search_title',
            'link' => 'pumukit_webtv_search_multimediaobjects',
        ),
        'pumukit_webtv_search_series' => array(
            'title' => 'menu.search_title',
            'link' => 'pumukit_webtv_search_series',
        ),
        'pumukit_webtv_categories_index' => array(
            'title' => 'menu.categories_title',
            'link' => 'pumukit_webtv_categories_index',
        ),
    );

    private $defaultSeriesLink = 'pumukit_webtv_series_index';
    private $defaultMultimediaLink = 'pumukit_webtv_multimediaobject_index';

    public function __construct(DocumentManager $documentManager, Container $container, Router $router, Session $session, $translator, $allTitle = 'All', $allRoute = 'pumukit_webtv_medialibrary_index', $homeTitle = 'home', $parentWeb = null)
    {
        $this->session = $session;
        $this->router = $router;
        $this->allTitle = $allTitle;
        $this->allRoute = $allRoute;
        $this->homeTitle = $homeTitle;
        $this->translator = $translator;
        $this->parentWeb = $parentWeb;
        $this->container = $container;
        $this->dm = $documentManager;

        $this->parentTag = $this->container->getParameter('pumukit_breadcrumb.tag');
        $this->defaultRouteBreadcrumb = $this->container->getParameter('pumukit_breadcrumb.default_route');
        $this->levelBreadcrumb = $this->container->getParameter('pumukit_breadcrumb.level');
        $this->selectBreadcrumb = $this->container->getParameter('pumukit_breadcrumb.select');
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if ($event->isMasterRequest()) {
            $request = $event->getRequest();

            $this->initBreadcrumb();

            if (in_array($request->attributes->get('_route'), $this->needReference) and $request->headers->has('referer')) {
                $this->session->set('breadcrumb_referer', $request->headers->get('referer'));
            }

            if (in_array($request->attributes->get('_route'), $this->allowRoutes)) {
                $this->addWay($request);
            }

            if (in_array($request->attributes->get('_route'), $this->needReference) and !$request->headers->has('referer')) {
                $this->addDefaultTagRoute();
            }

            if (in_array($request->attributes->get('_route'), $this->multimediaObjectRoutes)) {
                $oMultimediaId = $request->attributes->get('_route_params')['id'];
                $oMultimedia = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneById(new \MongoId($oMultimediaId));
                $seriesId = $oMultimedia->getSeries()->getId();

                $this->addToBreadcrumb($oMultimedia->getSeries()->getTitle(), $this->defaultSeriesLink, array('id' => new \MongoId($seriesId)));
                $this->addToBreadcrumb($oMultimedia->getTitle(), $this->defaultMultimediaLink, array('id' => new \MongoId($oMultimediaId)));
            } elseif (in_array($request->attributes->get('_route'), $this->seriesRoutes)) {
                $seriesId = $request->attributes->get('_route_params')['id'];
                $series = $this->dm->getRepository('PumukitSchemaBundle:Series')->findOneBy(array('id' => new \MongoId($seriesId)));
                $this->addToBreadcrumb($series->getTitle(), $this->defaultSeriesLink, array('id' => new \MongoId($seriesId)));
            } elseif (array_key_exists($request->attributes->get('_route'), $this->firstRoutes)) {
                $title = $this->container->getParameter($this->firstRoutes[$request->attributes->get('_route')]['title']);
                $link = $this->firstRoutes[$request->attributes->get('_route')]['link'];
                $this->addToBreadcrumb($title, $link);
            }

            /*elseif ('pumukit_webtv_announces_latestuploads' === $request->attributes->get('_route')) {
                $title = $this->container->getParameter('menu.announces_title');
                $link = 'pumukit_webtv_announces_latestuploads';
                $this->addToBreadcrumb($this->translator->trans($title), $link);
            } elseif ('pumukit_webtv_medialibrary_index' === $request->attributes->get('_route')) {
                $title = $this->container->getParameter('menu.mediateca_title');
                $link = 'pumukit_webtv_medialibrary_index';
                $this->addToBreadcrumb($this->translator->trans($title), $link);
            } elseif ('pumukit_webtv_search_multimediaobjects' === $request->attributes->get('_route')) {
                $title = $this->container->getParameter('menu.search_title');
                $link = 'pumukit_webtv_search_multimediaobjects';
                $this->addToBreadcrumb($this->translator->trans($title), $link);
            } elseif ('pumukit_webtv_search_series' === $request->attributes->get('_route')) {
                $title = $this->container->getParameter('menu.search_title');
                $link = 'pumukit_webtv_search_series';
                $this->addToBreadcrumb($this->translator->trans($title), $link);
            } elseif ('pumukit_webtv_categories_index' === $request->attributes->get('_route')) {
                $title = $this->container->getParameter('menu.categories_title');
                $link = 'pumukit_webtv_categories_index';
                $this->addToBreadcrumb($this->translator->trans($title), $link);
            }*/

            dump($this->breadcrumbs);
        }
    }

    private function initBreadcrumb()
    {
        $this->breadcrumbs = array();
        if ($this->parentWeb !== null) {
            $this->addToBreadcrumb($this->translator->trans($this->parentWeb['title']), $this->parentWeb['url'], array(), false);
        }
        $this->addToBreadcrumb($this->translator->trans($this->homeTitle), 'pumukit_webtv_index_index');
    }

    private function addWay($request)
    {
        // Series and reference tag
        if ($this->session->get('breadcrumb_referer') and (strpos($this->session->get('breadcrumb_referer'), '/series/tag/') or strpos($this->session->get('breadcrumb_referer'), '/multimediaobjects/tag/'))) {
            $aReference = explode('/', $this->session->get('breadcrumb_referer'));
            $tagCod = array_pop($aReference);

            $oTag = $this->dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => $tagCod));
            if (!$oTag->isDescendantOfByCod($this->parentTag)) {
                $oTag = $this->dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => $this->parentTag));
            }
        } elseif (in_array($request->attributes->get('_route'), $this->byTagRoutes)) {
            $tagCod = $request->attributes->get('_route_params')['tagCod'];
            $oTag = $this->dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => $tagCod));
        }

        $this->createTagsBreadcrumb($oTag);

        /* elseif ($this->session->get('breadcrumb_referer') and !in_array($request->attributes->get('_route'), $this->aWays)) {

            $aReferer = explode('/', $this->session->get('breadcrumb_referer'));
            $sReferer = array_pop($aReferer);

            if ('latestuploads' === $sReferer) {
                $title = $this->container->getParameter('menu.announces_title');
                $link = 'pumukit_webtv_announces_latestuploads';
            } elseif (strpos($this->session->get('breadcrumb_referer'), 'mediateca')) {
                $title = $this->container->getParameter('menu.mediateca_title');
                $link = 'pumukit_webtv_medialibrary_index';
            } elseif (strpos($this->session->get('breadcrumb_referer'), 'searchmultimediaobject')) {
                $title = $this->container->getParameter('menu.search_title');
                $link = 'pumukit_webtv_search_multimediaobjects';
            } elseif (strpos($this->session->get('breadcrumb_referer'), 'searchseries')) {
                $title = $this->container->getParameter('menu.search_title');
                $link = 'pumukit_webtv_search_series';
            }

            $this->addToBreadcrumb($this->translator->trans($title), $link);
        }*/
    }

    private function createTagsBreadcrumb($oTag)
    {
        $routeTags = explode('|', $oTag->getPath());
        if (0 === $this->levelBreadcrumb) {
            $routeTags = array_slice($routeTags, 2);
        } else {
            if ('first' !== $this->selectBreadcrumb) {
                $this->levelBreadcrumb = $this->levelBreadcrumb * -1;
            }
            if ($this->levelBreadcrumb > count($routeTags)) {
                $this->levelBreadcrumb = count($routeTags) - 2;
            }
            $routeTags = array_slice($routeTags, 2, $this->levelBreadcrumb);
        }

        foreach ($routeTags as $sTagCod) {
            if (!empty($sTagCod)) {
                $oTag = $this->dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => $sTagCod));
                $this->addToBreadcrumb($oTag->getTitle(), 'pumukit_webtv_bytag_series', array('tagCod' => $oTag->getCod()));
            }
        }
    }

    private function addToBreadcrumb($title, $sRoute, $aRouteParams = array(), $bGenerateRoute = true)
    {
        $link = ($bGenerateRoute) ? $this->router->generate($sRoute, $aRouteParams) : $sRoute;
        $this->breadcrumbs[] = array(
            'title' => $title,
            'link' => $link,
        );
    }

    private function addDefaultTagRoute()
    {
        if ('full' === $this->defaultRouteBreadcrumb) {
            $title = $this->container->getParameter('menu.mediateca_title');
            $link = 'pumukit_webtv_medialibrary_index';
            $this->addToBreadcrumb($this->translator->trans($title), $link);
        } elseif ('tag' === $this->defaultRouteBreadcrumb) {
            $oTag = $this->dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => $this->parentTag));
            $this->createTagsBreadcrumb($oTag);
        }
    }
}
