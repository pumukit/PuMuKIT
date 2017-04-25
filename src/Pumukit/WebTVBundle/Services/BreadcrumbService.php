<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;

class BreadcrumbService
{
    private $session;
    private $router;
    private $homeTitle;
    private $breadcrumbs;
    private $translator;
    private $dm;

    private $basicRoutes = array(
        'pumukit_webtv_index_index',
        'pumukit_webtv_bytag_series',
        'pumukit_webtv_bytag_multimediaobjects',
        'pumukit_webtv_multimediaobject_index',
        'pumukit_webtv_multimediaobject_magicindex',
        'pumukit_webtv_series_index',
        'pumukit_webtv_series_magicindex',
        'pumukit_webtv_announces_latestuploads',
        'pumukit_webtv_medialibrary_index',
        'pumukit_webtv_search_multimediaobjects',
        'pumukit_webtv_search_series',
        'pumukit_webtv_categories_index',
    );

    private $allowRoutes = array(
        'pumukit_webtv_bytag_series',
        'pumukit_webtv_bytag_multimediaobjects',
        'pumukit_webtv_multimediaobject_index',
        'pumukit_webtv_multimediaobject_magicindex',
        'pumukit_webtv_series_index',
        'pumukit_webtv_series_magicindex',
    );

    private $notDefaultTagRoute = array(
        'pumukit_webtv_announces_latestuploads',
        'pumukit_webtv_medialibrary_index',
        'pumukit_webtv_search_multimediaobjects',
        'pumukit_webtv_search_series',
        'pumukit_webtv_categories_index',
        'pumukit_webtv_channel_series',
        'pumukit_webtv_index_index',
    );

    private $needReference = array(
        'pumukit_webtv_multimediaobject_index',
        'pumukit_webtv_multimediaobject_magicindex',
        'pumukit_webtv_series_index',
        'pumukit_webtv_series_magicindex',
    );

    private $defaultSeriesLink = 'pumukit_webtv_series_index';
    private $defaultMultimediaLink = 'pumukit_webtv_multimediaobject_index';
    private $byTagRoutes = array(
        'pumukit_webtv_bytag_series',
        'pumukit_webtv_bytag_multimediaobjects',
    );

    private $seriesRoutes = array(
        'pumukit_webtv_series_index',
        'pumukit_webtv_series_magicindex',
    );
    private $multimediaObjectRoutes = array(
        'pumukit_webtv_multimediaobject_index',
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

    public function __construct(DocumentManager $documentManager, Router $router, Session $session, $translator, $homeTitle = 'home', $parentWeb = null, $breadcrumbTag = 'ITUNESU', $defaultRoute = 'full', $breadcrumbLevel = 0, $breadcrumbSelect = 'start', $channel1 = 'University', $channel2 = 'Bussiness', $channel3 = 'Natural Sciences', $channel4 = 'Law', $channel5 = 'Humanities', $channel6 = 'Health & Medicine', $channel7 = 'Social Matters & Education', $announceTitle = 'Latest Uploads', $mediatecaTitle = 'Full Catalogue', $searchTitle = 'Multimedia objects search', $categoriesTitle = 'By subject catalogue')
    {
        $this->session = $session;
        $this->router = $router;
        $this->homeTitle = $homeTitle;
        $this->translator = $translator;
        $this->parentWeb = $parentWeb;
        $this->dm = $documentManager;

        $this->parentTag = $breadcrumbTag;
        $this->defaultRouteBreadcrumb = $defaultRoute;
        $this->levelBreadcrumb = $breadcrumbLevel;
        $this->selectBreadcrumb = $breadcrumbSelect;

        $this->channel[1] = $channel1;
        $this->channel[2] = $channel2;
        $this->channel[3] = $channel3;
        $this->channel[4] = $channel4;
        $this->channel[5] = $channel5;
        $this->channel[6] = $channel6;
        $this->channel[7] = $channel7;

        $this->title['menu.announces_title'] = $announceTitle;
        $this->title['menu.mediateca_title'] = $mediatecaTitle;
        $this->title['menu.search_title'] = $searchTitle;
        $this->title['menu.categories_title'] = $categoriesTitle;
    }

    public function createBreadcrumb($request)
    {
        $this->initBreadcrumb();
        $this->addToSession($request);
        $this->addWay($request);

        if (in_array($request->attributes->get('_route'), $this->needReference) and !$request->headers->has('referer')) {
            $this->addDefaultTagRoute($request);
        } elseif (in_array($request->attributes->get('_route'), $this->needReference) and (count($this->breadcrumbs) == 2 and $this->parentWeb !== null) or (count($this->breadcrumbs) == 1 and $this->parentWeb === null)) {
            $this->addDefaultTagRoute($request);
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
            $title = $this->title[$this->firstRoutes[$request->attributes->get('_route')]['title']];
            $link = $this->firstRoutes[$request->attributes->get('_route')]['link'];
            $this->addToBreadcrumb($title, $link);
        } elseif ('pumukit_webtv_channel_series' === $request->attributes->get('_route')) {
            $channelNumber = $request->attributes->get('_route_params')['channelNumber'];
            $title = $this->channel[$channelNumber];
            $link = $request->attributes->get('_route');
            $this->addToBreadcrumb($this->translator->trans($title), $link, $request->attributes->get('_route_params'));
        } elseif (!in_array($request->attributes->get('_route'), $this->basicRoutes)) {
            $aTitle = explode('/', $request->server->get('DOCUMENT_URI'));
            $title = array_pop($aTitle);
            $link = $request->server->get('DOCUMENT_URI');
            $this->addToBreadcrumb($this->translator->trans($title), $link, array(), false);
        }

        $request->attributes->set('breadcrumb', $this->breadcrumbs);
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
        if (!in_array($request->attributes->get('_route'), $this->allowRoutes)) {
            return;
        }

        if ($this->session->get('breadcrumb_referer') and (strpos($this->session->get('breadcrumb_referer'), '/series/tag/') or strpos($this->session->get('breadcrumb_referer'), '/multimediaobjects/tag/')) and !in_array($request->attributes->get('_route'), $this->byTagRoutes)) {
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

        if (isset($oTag)) {
            $this->createTagsBreadcrumb($oTag);
        } elseif ($this->session->get('breadcrumb_referer')) {
            $this->addLastPath();
        }
    }

    private function addLastPath()
    {
        if (strpos($this->session->get('breadcrumb_referer'), 'latestuploads')) {
            $title = $this->title[$this->firstRoutes['pumukit_webtv_announces_latestuploads']['title']];
            $link = $this->firstRoutes['pumukit_webtv_announces_latestuploads']['link'];
            $this->addToBreadcrumb($this->translator->trans($title), $link);
        } elseif (strpos($this->session->get('breadcrumb_referer'), 'mediateca')) {
            $title = $this->title[$this->firstRoutes['pumukit_webtv_medialibrary_index']['title']];
            $link = $this->firstRoutes['pumukit_webtv_medialibrary_index']['link'];
            $this->addToBreadcrumb($this->translator->trans($title), $link);
        } elseif (strpos($this->session->get('breadcrumb_referer'), 'searchmultimediaobject')) {
            $title = $this->title[$this->firstRoutes['pumukit_webtv_search_multimediaobjects']['title']];
            $link = $this->firstRoutes['pumukit_webtv_search_multimediaobjects']['link'];
            $this->addToBreadcrumb($this->translator->trans($title), $link);
        } elseif (strpos($this->session->get('breadcrumb_referer'), 'searchseries')) {
            $title = $this->title[$this->firstRoutes['pumukit_webtv_search_series']['title']];
            $link = $this->firstRoutes['pumukit_webtv_search_series']['link'];
            $this->addToBreadcrumb($this->translator->trans($title), $link);
        } elseif (strpos($this->session->get('breadcrumb_referer'), 'categories')) {
            $title = $this->title[$this->firstRoutes['pumukit_webtv_categories_index']['title']];
            $link = $this->firstRoutes['pumukit_webtv_categories_index']['link'];
            $this->addToBreadcrumb($this->translator->trans($title), $link);
        } elseif (strpos($this->session->get('breadcrumb_referer'), 'channel')) {
            $aReferer = explode('/', $this->session->get('breadcrumb_referer'));
            $sReferer = array_pop($aReferer);
            $sReferer = explode('.', $sReferer);
            $title = $this->channel[$sReferer[0]];
            $link = 'pumukit_webtv_channel_series';
            $params = array('channelNumber' => $sReferer[0]);
            $this->addToBreadcrumb($this->translator->trans($title), $link, $params);
        }
    }

    private function addDefaultTagRoute($request)
    {
        if (in_array($request->attributes->get('_route'), $this->notDefaultTagRoute) or ((count($this->breadcrumbs) > 2 and $this->parentWeb !== null) or (count($this->breadcrumbs) > 1 and $this->parentWeb === null))) {
            return;
        }

        if ('full' === $this->defaultRouteBreadcrumb) {
            $title = $this->title['menu.mediateca_title'];
            $link = 'pumukit_webtv_medialibrary_index';
            $this->addToBreadcrumb($this->translator->trans($title), $link);
        }
    }

    private function addToSession($request)
    {
        if (in_array($request->attributes->get('_route'), $this->needReference) and $request->headers->has('referer')) {
            if (in_array($request->attributes->get('_route'), $this->seriesRoutes)) {
                if ($request->headers->get('referer') !== $this->session->get('referer_breadcrumb')) {
                    $this->session->set('breadcrumb_referer', $request->headers->get('referer'));
                }
            } elseif (!strpos($request->headers->get('referer'), '/series/tag') and !strpos($request->headers->get('referer'), '/series/')) {
                $this->session->set('breadcrumb_referer', $request->headers->get('referer'));
            }
        }
    }

    private function createTagsBreadcrumb($oTag)
    {
        $routeTags = explode('|', $oTag->getPath());
        $routeTags = array_filter($routeTags, create_function('$value', 'return $value !== "";'));
        if (0 === $this->levelBreadcrumb) {
            $routeTags = array_slice($routeTags, 2);
        } else {
            $offset = 2;
            if ('start' !== $this->selectBreadcrumb) {
                $offset = $this->levelBreadcrumb * -1;
            }
            if ($this->levelBreadcrumb > count($routeTags)) {
                $this->levelBreadcrumb = count($routeTags) - 2;
            }
            $routeTags = array_slice($routeTags, $offset, $this->levelBreadcrumb);
        }

        foreach ($routeTags as $sTagCod) {
            $oTag = $this->dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => $sTagCod));
            $this->addToBreadcrumb($oTag->getTitle(), 'pumukit_webtv_bytag_series', array('tagCod' => $oTag->getCod()));
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
}
