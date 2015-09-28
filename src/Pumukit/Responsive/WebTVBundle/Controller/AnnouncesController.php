<?php

namespace Pumukit\Responsive\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AnnouncesController extends Controller
{
    private $page_elem = 2;
    /**
     * @Route("/latestuploads", name="pumukit_responsive_webtv_announces_latestuploads")
     * @Template()
     */
    public function latestUploadsAction(Request $request)
    {
        $this->get('pumukit_responsive_web_tv.breadcrumbs')->addList('Latest Uploads', 'pumukit_responsive_webtv_announces_latestuploads');
        $last = $this->get('pumukitschema.announce')->getLast(100000000000);
        dump($last);
        $max_page = count( $last )/$this->page_elem;
        return array('last' => $last,
                     'max_page' => $max_page);
    }
    /**
     * @Route("/latestuploads/pager", name="pumukit_responsive_webtv_announces_latestuploads_pager")
     * @Template()
     */
    public function latestUploadsPagerAction(Request $request, $page = 1)
    {
        $page = $request->query->get("page", 0);

        $this->get('pumukit_responsive_web_tv.breadcrumbs')->addList('Latest Uploads', 'pumukit_responsive_webtv_announces_latestuploads');
        $last = $this->get('pumukitschema.announce')->getLast(100000000000);
        
        $last = array_slice( $last, $page*$this->page_elem, $this->page_elem);
        return array('last' => $last);
    }

}
