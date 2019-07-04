<?php

namespace Pumukit\WebTVBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;

/**
 * Class AnnouncesController.
 */
class AnnouncesController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/latestuploads", name="pumukit_webtv_announces_latestuploads")
     * @Template("PumukitWebTVBundle:Announces:template.html.twig")
     *
     * @return array
     */
    public function latestUploadsAction()
    {
        $templateTitle = $this->container->getParameter('menu.announces_title');
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle, 'pumukit_webtv_announces_latestuploads');

        return [
            'template_title' => $templateTitle,
            'objectByCol' => $this->container->getParameter('columns_objs_announces'),
            'show_info' => false,
            'show_more' => false,
        ];
    }

    /**
     * @return string
     */
    protected function getLatestUploadsPagerTemplate()
    {
        return 'PumukitWebTVBundle:Announces:template_pager.html.twig';
    }

    /**
     * @Route("/latestuploads/pager", name="pumukit_webtv_announces_latestuploads_pager")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function latestUploadsPagerAction(Request $request)
    {
        [$numberCols, $showPudenew, $useRecordDate] = $this->getParameters();

        $announcesService = $this->get('pumukitschema.announce');

        $dateRequest = $request->query->get('date', 0); //Use to queries for month and year to reduce formatting and unformatting.
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "01/$dateRequest 00:00:00");
        if (!$date) {
            throw $this->createNotFoundException();
        }
        [$date, $last] = $announcesService->getNextLatestUploads($date, $showPudenew, $useRecordDate);

        $response = new Response();
        $dateHeader = '---';

        if (!empty($last)) {
            $response = new Response(
                $this->renderView(
                    $this->getLatestUploadsPagerTemplate(),
                    [
                        'last' => $last,
                        'date' => $date,
                        'objectByCol' => $numberCols,
                        'show_info' => false,
                        'show_more' => false,
                    ]
                ), 200
            );
            $dateHeader = $date->format('m/Y');
            $response->headers->set('X-Date-Month', $date->format('m'));
            $response->headers->set('X-Date-Year', $date->format('Y'));
        }

        $response->headers->set('X-Date', $dateHeader);

        return $response;
    }

    /**
     * To extends this controller.
     *
     * @return array
     */
    protected function getParameters()
    {
        return [
            $this->container->getParameter('columns_objs_announces'),
            $this->container->getParameter('show_latest_with_pudenew'),
            $this->container->getParameter('use_record_date_announces'),
        ];
    }
}
