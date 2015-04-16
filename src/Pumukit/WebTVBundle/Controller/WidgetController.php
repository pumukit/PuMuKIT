<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class WidgetController extends Controller
{
    /**
     * @Template()
     */
    public function menuAction()
    {
      return array();
    }
    
    /**
     * @Template()
     */
    public function statsAction()
    {
      $counts = array('series' => 12,
                      'mms' => 1235,
                      'hours' => 3214153);
      return array('counts' => $counts);
    }

    /**
     * @Template()
     */
    public function contactAction()
    {
      return array();
    }

}
