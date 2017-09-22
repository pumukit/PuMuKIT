<?php

namespace Pumukit\WebTVBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnnouncesController extends Controller implements WebTVController
{
    /**
     * @Route("/latestuploads", name="pumukit_webtv_announces_latestuploads")
     * @Template()
     */
    public function latestUploadsAction(Request $request)
    {
        $templateTitle = $this->container->getParameter('menu.announces_title');
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle, 'pumukit_webtv_announces_latestuploads');

        return array('template_title' => $templateTitle);
    }

    /**
     * @Route("/latestuploads/pager", name="pumukit_webtv_announces_latestuploads_pager")
     * @Template()
     */
    public function latestUploadsPagerAction(Request $request)
    {
        $numberCols = $this->container->getParameter('columns_objs_announces');
        $showPudenew = $this->container->getParameter('show_latest_with_pudenew');

        $announcesService = $this->get('pumukitschema.announce');

        $dateRequest = $request->query->get('date', 0); //Use to queries for month and year to reduce formatting and unformatting.
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "01/$dateRequest 00:00:00");
        if (!$date) {
            throw $this->createNotFoundException();
        }
        list($date, $last) = $announcesService->getNextLatestUploads($date, $showPudenew);
        if (empty($last)) {
            $dateHeader = '---';
        } else {
            $dateHeader = $date->format('m/Y');
        }

        $response = new Response($this->renderView('PumukitWebTVBundle:Announces:latestUploadsPager.html.twig', array('last' => $last, 'date' => $date, 'number_cols' => $numberCols)), 200);
        $response->headers->set('X-Date', $dateHeader);
        $response->headers->set('X-Date-Month', $date->format('m'));
        $response->headers->set('X-Date-Year', $date->format('Y'));

        return $response;
    }
}
