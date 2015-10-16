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

        $dateRequest = $request->query->get('date', 0);

        $dateStart = \DateTime::createFromFormat('d/m/Y', "01/$dateRequest");
        $dateEnd = clone $dateStart;
        $dateStart->modify('first day of next month');
        $dateStart->modify('-1 day');
        $dateEnd->modify('last day of next month');

        list($dateStart, $dateEnd, $last) = $announcesService->getNextLatestUploads($dateStart, $dateEnd);

        if (empty($last)) {
            $dateHeader = '---';
        } else {
            $dateHeader = $dateEnd->format('m/Y');
        }

        $response = new Response($this->renderView('PumukitWebTVBundle:Announces:latestUploadsPager.html.twig', array('last' => $last, 'date' => $dateEnd)), 200);
        $response->headers->set('X-Date', $dateHeader);
        $response->headers->set('X-Date-Month', $dateEnd->format('m'));
        $response->headers->set('X-Date-Year', $dateEnd->format('Y'));

        return $response;
    }
}
