<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnnouncesController extends Controller
{
    /**
     * @Route("/latestuploads", name="pumukit_webtv_announces_latestuploads")
     * @Template()
     */
    public function latestUploadsAction(Request $request)
    {
        $this->get('pumukit_web_tv.breadcrumbs')->addList('Latest Uploads', 'pumukit_webtv_announces_latestuploads');
    }
    /**
     * @Route("/latestuploads/pager", name="pumukit_webtv_announces_latestuploads_pager")
     * @Template()
     */
    public function latestUploadsPagerAction(Request $request)
    {
        $announcesService = $this->get('pumukitschema.announce');

        $dateRequest = $request->query->get('date', 0);//Use to queries for month and year to reduce formatting and unformatting.
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "01/$dateRequest 00:00:00");
        list($date, $last) = $announcesService->getNextLatestUploads($date);
        if (empty($last)) {
            $dateHeader = '---';
        } else {
            $dateHeader = $date->format('m/Y');
        }

        $response = new Response($this->renderView('PumukitWebTVBundle:Announces:latestUploadsPager.html.twig', array('last' => $last, 'date' => $date)), 200);
        $response->headers->set('X-Date', $dateHeader);
        $response->headers->set('X-Date-Month', $date->format('m'));
        $response->headers->set('X-Date-Year', $date->format('Y'));

        return $response;
    }
}
