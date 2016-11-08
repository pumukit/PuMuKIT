<?php

namespace Pumukit\WebTVBundle\Services;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class BreadcrumbsService
{
    private $session;
    private $router;
    private $allTitle;
    private $allRoute;
    private $homeTitle;
    private $breadcrumbs;
    private $translator;

    public function __construct(Router $router, Session $session, $translator, $allTitle = 'All', $allRoute = 'pumukit_webtv_medialibrary_index', $homeTitle = 'home', $parentWeb = null)
    {
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
            $this->session->set('breadcrumbs/routeParameters', array());
        }
        $this->breadcrumbs = array();
        if ($this->parentWeb !== null) {
            $this->breadcrumbs = array(array('title' => $this->parentWeb['title'], 'link' => $this->parentWeb['url']));
        }
        $this->breadcrumbs[] = array('title' => $this->homeTitle, 'link' => $this->router->generate('pumukit_webtv_index_index'));
    }

    public function reset()
    {
        $this->session->set('breadcrumbs/title', $this->translator->trans($this->allTitle));
        $this->session->set('breadcrumbs/routeName', $this->allRoute);
        $this->session->set('breadcrumbs/routeParameters', array());
        $this->breadcrumbs = array();
        if ($this->parentWeb !== null) {
            $this->breadcrumbs = array(array('title' => $this->parentWeb['title'], 'link' => $this->parentWeb['url']));
        }
        $this->breadcrumbs[] = array('title' => $this->homeTitle, 'link' => $this->router->generate('pumukit_webtv_index_index'));
    }

    public function addList($title, $routeName, array $routeParameters = array(), $forceTranslation = false)
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

    public function addSeries(Series $series)
    {
        if (1 == count($this->breadcrumbs)) {
            $this->add($this->session->get('breadcrumbs/title', $this->allTitle),
                 $this->session->get('breadcrumbs/routeName', $this->allRoute),
                 $this->session->get('breadcrumbs/routeParameters', array()));
        }

        $this->add($series->getTitle(), 'pumukit_webtv_series_index', array('id' => $series->getId()));
    }

    public function addMultimediaObject(MultimediaObject $multimediaObject)
    {
        $this->addSeries($multimediaObject->getSeries());
        $this->add($multimediaObject->getTitle(), 'pumukit_webtv_multimediaobject_index', array('id' => $multimediaObject->getId()));
    }

    public function add($title, $routeName, array $routeParameters = array())
    {
        $this->breadcrumbs[] = array('title' => $title,
                                 'link' => $this->router->generate($routeName, $routeParameters), );
    }

    public function getBreadcrumbs()
    {
        return $this->breadcrumbs;
    }
}
