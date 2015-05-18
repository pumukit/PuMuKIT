<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Pumukit\WebTVBundle\Controller\MultimediaObjectController as Base;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectController extends Base
{
    public function preExecute(MultimediaObject $multimediaObject)
    {
      if($opencasturl = $multimediaObject->getProperty("opencasturl")) {
          $this->incNumView($multimediaObject);
          return $this->redirect($opencasturl);
      }
    }
}