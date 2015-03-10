<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;

class SeriesController extends Controller
{
    /**
     * @Route("/series/{id}")
     * @Template()
     */
    public function indexAction(Series $series, Request $request)
    {
        $limit = 2;
        $page =  $request->get("page", 1);

    	$multimediaObjects = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $queryBuilder = $multimediaObjects->createStandardQueryBuilder();
        $queryBuilder->field('series')->equals($series->getId());

        $adapter = new DoctrineODMMongoDBAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);
    	
        return array('series' => $series, 'multimediaObjects' => $pagerfanta);
    }
}