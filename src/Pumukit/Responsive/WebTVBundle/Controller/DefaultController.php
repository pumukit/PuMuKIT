<?php

namespace Pumukit\Responsive\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class DefaultController extends Controller
{
    /**
     * @Route("/ilikecocoa/andducks/wowthisissuchalongurl/pumukitrocks/mynameis/{name}", name="pumukit_responsive_webtv_default_cocoa")
     * @Template()
     */
    public function indexAction($name)
    {
        return array('name' => $name);
    }
}
