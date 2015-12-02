<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Pumukit\WebTVBundle\Controller\IndexController as Base;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class IndexController extends Base
{
    /**
     * @Route("/", name="pumukit_webtv_index_index")
     * @Template()
     */
    public function indexAction()
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $eventRepo = $dm->getRepository('PumukitLiveBundle:Event');
        $event = $eventRepo->findOneByHoursEvent(3);

        $this->get('pumukit_web_tv.breadcrumbs')->reset();
        return array('event' => $event);
    }
}