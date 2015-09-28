<?php

namespace Pumukit\Responsive\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class AnnouncesController extends Controller
{
    /**
     * @Route("/latestuploads", name="pumukit_responsive_webtv_announces_latestuploads")
     * @Template()
     */
    public function latestUploadsAction()
    {
        $this->get('pumukit_responsive_web_tv.breadcrumbs')->addList('Latest Uploads', 'pumukit_responsive_webtv_announces_latestuploads');
        
        $last = $this->get('pumukitschema.announce')->getLast(100000000000);
        return array('last' => $last);
    }

}
