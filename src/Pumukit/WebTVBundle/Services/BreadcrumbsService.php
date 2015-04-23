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
  private $breadcrumbs;


  public function __construct(Router $router, Session $session, $allTitle='All', $allRoute="pumukit_webtv_medialibrary_index")
  {
    $this->session = $session;
    $this->router = $router;
    $this->allTitle = $allTitle;
    $this->allRoute = $allRoute;
    $this->breadcrumbs = array(array("title" => "Home", "link" => $this->router->generate("pumukit_webtv_index_index")));
  }

  
  public function addList($title, $routeName, array $routeParameters = array())
  {
    $this->session->set('breadcrumbs/title', $title);
    $this->session->set('breadcrumbs/routeName', $routeName);
    $this->session->set('breadcrumbs/routeParameters', $routeParameters);
    $this->add(1, $title, $routeName, $routeParameters);
  }


  public function addSeries(Series $series)
  {
    if (1 == count($this->breadcrumbs)){
      $this->add(1, 
                 $this->session->get('breadcrumbs/title', $this->allTitle),
                 $this->session->get('breadcrumbs/routeName', $this->allRoute),
                 $this->session->get('breadcrumbs/routeParameters', array()));
    }
    
    $this->add(2, $series->getTitle(), "pumukit_webtv_series_index", array("id" => $series->getId()));
  }


  public function addMultimediaObject(MultimediaObject $multimediaObject)
  {
    $this->addSeries($multimediaObject->getSeries());
    $this->add(3, $multimediaObject->getTitle(), "pumukit_webtv_multimediaobject_index", array("id" => $multimediaObject->getId()));
  }


  private function add($index, $title, $routeName, array $routeParameters = array())
  {
    $this->breadcrumbs[$index] = array("title" => $title, 
                                       "link" => $this->router->generate($routeName, $routeParameters));
  }

  public function getBreadcrumbs()
  {
    return $this->breadcrumbs;
  }
}