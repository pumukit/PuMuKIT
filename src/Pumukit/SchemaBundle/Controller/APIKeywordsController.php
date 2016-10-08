<?php

namespace Pumukit\SchemaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/api/keywords")
 */
class APIKeywordsController extends Controller
{
    /**
     * @Route("/series.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function seriesAction(Request $request)
    {
        return $this->base('PumukitSchemaBundle:Series', $request->getRequestFormat(), 1000);
    }

    /**
     * @Route("/mmobj.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function mmobjAction(Request $request)
    {
        return $this->base('PumukitSchemaBundle:MultimediaObject', $request->getRequestFormat(), 1000);
    }

    private function base($collName, $format = 'json', $limit = null)
    {
        $coll = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getDocumentCollection($collName);
        $serializer = $this->get('serializer');

        $pipeline = array(
            array('$project' => array( 'k' => '$keywords.en', '_id' => false )),
            array('$match' => array('k' => array('$ne' => ''))),
            array('$unwind' => array('path' => '$k')),
            array('$group' => array('_id' => '$k', 'count' => array( '$sum' => 1 ))),
            array('$sort' => array('count' => -1)),
        );

        if ($limit) {
            $pipeline[] = array('$limit' => $limit);
        }

        $kws = $coll->aggregate($pipeline);
        $data = $serializer->serialize($kws->toArray(), $format);
        return new Response($data);
    }

}
