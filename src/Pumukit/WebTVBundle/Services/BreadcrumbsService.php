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
  private $allRoute;
  private $breadcrumbs;

  
  public function __construct(Router $router, Session $session, $allRoute="pumukit_webtv_medialibrary_index")
  {
    $this->session = $session;
    $this->router = $router;
    $this->allRoute = $allRoute;
    $this->breadcrumbs = array(array("title" => "Home", "link" => $this->router->generate("pumukit_webtv_index_index")));
  }
  

  public function addList($title, $routeName, $routeParameters = array())
  {
    $this->add(1, $title, $routeName, $routeParameters = array());
  }

  public function addSeries(Series $series)
  {
    if (1 == count($this->breadcrumbs)){
      $this->add(1, "All", $this->allRoute);
    }
    
    $this->add(2, $series->getTitle(), "pumukit_webtv_series_index", array("id" => $series->getId()));
  }


  public function addMultimediaObject(MultimediaObject $multimediaObject)
  {
    $this->addSeries($multimediaObject->getSeries());
    $this->add(3, $multimediaObject->getTitle(), "pumukit_webtv_multimediaobject_index", array("id" => $multimediaObject->getId()));
  }


  private function add($index, $title, $routeName, $routeParameters = array())
  {
    $this->breadcrumbs[$index] = array("title" => $title, 
                                       "link" => $this->router->generate($routeName, $routeParameters));
  }

  public function getBreadcrumbs()
  {
    return $this->breadcrumbs;
  }
}