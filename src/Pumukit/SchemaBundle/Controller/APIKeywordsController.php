<?php

namespace Pumukit\SchemaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

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
        return $this->base(
            Series::class,
            $request->getLocale(),
            $request->getRequestFormat(),
            1000
        );
    }

    /**
     * @Route("/mmobj.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function mmobjAction(Request $request)
    {
        return $this->base(
            MultimediaObject::class,
            $request->getLocale(),
            $request->getRequestFormat(),
            1000
        );
    }

    private function base($collName, $lang, $format = 'json', $limit = null)
    {
        $coll = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getDocumentCollection($collName);
        $serializer = $this->get('jms_serializer');

        $pipeline = [
            ['$project' => ['k' => '$keywords.'.$lang, '_id' => false]],
            ['$match' => ['k' => ['$ne' => '']]],
            ['$unwind' => '$k'],
            ['$group' => ['_id' => '$k', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]],
        ];

        if ($limit) {
            $pipeline[] = ['$limit' => $limit];
        }

        $kws = $coll->aggregate($pipeline, ['cursor' => []]);
        $data = $serializer->serialize($kws->toArray(), $format);

        return new Response($data);
    }
}
