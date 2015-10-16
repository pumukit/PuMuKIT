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
        $date_request = $request->query->get('date', 0);
        $repository_mms = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $queryBuilderMms = $repository_mms->createQueryBuilder();
        $repository_series = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $queryBuilderSeries = $repository_series->createQueryBuilder();

        $date_ini = \DateTime::createFromFormat('d/m/Y', "01/$date_request");
        $date_fin = clone $date_ini;
        $date_ini->modify('first day of next month');
        $date_ini->modify('-1 day');
        $date_fin->modify('last day of next month');

        $counter = 0;
        do {
            ++$counter;
            $date_ini->modify('last day of last month');
            $date_fin->modify('last day of last month');

            $queryBuilderMms->field('public_date')->range($date_ini, $date_fin);
            $queryBuilderSeries->field('public_date')->range($date_ini, $date_fin);
            $queryBuilderSeries->field('announce')->equals(true);
            $queryBuilderMms->field('tags.cod')->equals('PUDENEW');
            $lastMms = $queryBuilderMms->getQuery()->execute();
            $lastSeries = $queryBuilderSeries->getQuery()->execute();
            $last = array();
            foreach ($lastSeries as $serie) {
                $last[] = $serie;
            }
            foreach ($lastMms as $mm) {
                $last[] = $mm;
            }
        } while (empty($last) && $counter < 24);

        if (empty($last)) {
            $date_header = '---';
        } else {
            $date_header = $date_fin->format('m/Y');
        }

        usort($last, function ($a, $b) {
            $date_a = $a->getPublicDate();
            $date_b = $b->getPublicDate();
            if ($date_a == $date_b) {
                return 0;
            }

            return $date_a < $date_b ? 1 : -1;
        });

        $response = new Response($this->renderView('PumukitWebTVBundle:Announces:latestUploadsPager.html.twig', array('last' => $last, 'date' => $date_fin)), 200);
        $response->headers->set('X-Date', $date_header);
        $response->headers->set('X-Date-Month', $date_fin->format('m'));
        $response->headers->set('X-Date-Year', $date_fin->format('Y'));

        return $response;
    }
}
