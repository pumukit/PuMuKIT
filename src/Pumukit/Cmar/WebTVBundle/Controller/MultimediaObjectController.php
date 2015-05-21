<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Pumukit\WebTVBundle\Controller\MultimediaObjectController as Base;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectController extends Base
{
    public function preExecute(MultimediaObject $multimediaObject)
    {
        if ($opencasturl = $multimediaObject->getProperty("opencasturl")) {
            $this->updateBreadcrumbs($multimediaObject);
            $this->incNumView($multimediaObject);
            // TODO is_old_browser
            return $this->render("PumukitCmarWebTVBundle:MultimediaObject:opencast.html.twig", array("multimediaObject" => $multimediaObject));
        }
    }
}