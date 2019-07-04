<?php

namespace Pumukit\WebTVBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class BreadcrumbsService.
 */
class BreadcrumbsService
{
    private $session;
    private $router;
    private $allTitle;
    private $allRoute;
    private $homeTitle;
    private $breadcrumbs;
    private $translator;
    private $parentWeb;

    /**
     * BreadcrumbsService constructor.
     *
     * @param RouterInterface  $router
     * @param SessionInterface $session
     * @param                  $translator
     * @param string           $allTitle
     * @param string           $allRoute
     * @param string           $homeTitle
     * @param null             $parentWeb
     */
    public function __construct(
        RouterInterface $router,
        SessionInterface $session,
        $translator,
        $allTitle = 'All',
        $allRoute = 'pumukit_webtv_medialibrary_index',
        $homeTitle = 'home',
        $parentWeb = null
    ) {
        $this->session = $session;
        $this->router = $router;
        $this->allTitle = $allTitle;
        $this->allRoute = $allRoute;
        $this->homeTitle = $homeTitle;
        $this->translator = $translator;
        $this->parentWeb = $parentWeb;
        $this->init();
    }

    public function init()
    {
        if (!$this->session->has('breadcrumbs/title')) {
            $this->session->set('breadcrumbs/title', $this->translator->trans($this->allTitle));
        }
        if (!$this->session->has('breadcrumbs/routeParameters')) {
            $this->session->set('breadcrumbs/routeName', $this->allRoute);
        }
        if (!$this->session->has('breadcrumbs/routeParameters')) {
            $this->session->set('breadcrumbs/routeParameters', []);
        }
        $this->breadcrumbs = [];
        if (null !== $this->parentWeb) {
            $this->breadcrumbs = [['title' => $this->parentWeb['title'], 'link' => $this->parentWeb['url']]];
        }
        $this->breadcrumbs[] = ['title' => $this->homeTitle, 'link' => $this->router->generate('pumukit_webtv_index_index')];
    }

    public function reset()
    {
        $this->session->set('breadcrumbs/title', $this->translator->trans($this->allTitle));
        $this->session->set('breadcrumbs/routeName', $this->allRoute);
        $this->session->set('breadcrumbs/routeParameters', []);
        $this->breadcrumbs = [];
        if (null !== $this->parentWeb) {
            $this->breadcrumbs = [['title' => $this->parentWeb['title'], 'link' => $this->parentWeb['url']]];
        }
        $this->breadcrumbs[] = ['title' => $this->homeTitle, 'link' => $this->router->generate('pumukit_webtv_index_index')];
    }

    /**
     * @param       $title
     * @param       $routeName
     * @param array $routeParameters
     * @param bool  $forceTranslation
     */
    public function addList($title, $routeName, array $routeParameters = [], $forceTranslation = false)
    {
        if ($forceTranslation) {
            $title = $this->translator->trans($title);
        }
        $this->reset();
        $this->session->set('breadcrumbs/title', $title);
        $this->session->set('breadcrumbs/routeName', $routeName);
        $this->session->set('breadcrumbs/routeParameters', $routeParameters);
        $this->add($title, $routeName, $routeParameters);
    }

    /**
     * @param Series $series
     */
    public function addSeries(Series $series)
    {
        if (1 == count($this->breadcrumbs)) {
            $this->add(
                $this->session->get('breadcrumbs/title', $this->allTitle),
                $this->session->get('breadcrumbs/routeName', $this->allRoute),
                $this->session->get('breadcrumbs/routeParameters', [])
            );
        }

        if (!$series->isHide()) {
            $this->add($series->getTitle(), 'pumukit_webtv_series_index', ['id' => $series->getId()]);
        }
    }

    /**
     * @param MultimediaObject $multimediaObject
     */
    public function addMultimediaObject(MultimediaObject $multimediaObject)
    {
        $this->addSeries($multimediaObject->getSeries());

        $title = $multimediaObject->getTitle();
        if ($multimediaObject->isPublished()) {
            $routeName = 'pumukit_webtv_multimediaobject_index';
            $routeParameters = ['id' => $multimediaObject->getId()];
        } else {
            $routeName = 'pumukit_webtv_multimediaobject_magicindex';
            $routeParameters = ['secret' => $multimediaObject->getSecret()];
        }

        $this->add($title, $routeName, $routeParameters);
    }

    /**
     * @param       $title
     * @param       $routeName
     * @param array $routeParameters
     */
    public function add($title, $routeName, array $routeParameters = [])
    {
        $this->breadcrumbs[] = [
            'title' => $title,
            'link' => $this->router->generate($routeName, $routeParameters),
        ];
    }

    /**
     * @return mixed
     */
    public function getBreadcrumbs()
    {
        return $this->breadcrumbs;
    }

    /**
     * @param $title
     */
    public function setTitle($title)
    {
        if ((null !== $this->parentWeb) && (isset($this->breadcrumbs[1]['title']))) {
            $this->breadcrumbs[1]['title'] = $title;
        } else {
            $this->breadcrumbs[0]['title'] = $title;
        }
    }
}
