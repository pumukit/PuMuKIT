<?php

namespace Pumukit\SchemaBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\SerializerService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/keywords")
 */
class APIKeywordsController extends AbstractController
{
    /**
     * @Route("/series.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function seriesAction(Request $request, DocumentManager $documentManager, SerializerService $serializer)
    {
        return $this->base(
            $documentManager,
            $serializer,
            Series::class,
            $request->getLocale(),
            $request->getRequestFormat(),
            1000
        );
    }

    /**
     * @Route("/mmobj.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function mmobjAction(Request $request, DocumentManager $documentManager, SerializerService $serializer)
    {
        return $this->base(
            $documentManager,
            $serializer,
            MultimediaObject::class,
            $request->getLocale(),
            $request->getRequestFormat(),
            1000
        );
    }

    private function base(DocumentManager $documentManager, SerializerService $serializer, $collName, $lang, $format = 'json', $limit = null)
    {
        $coll = $documentManager->getDocumentCollection($collName);

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
        $data = $serializer->dataSerialize($kws->toArray(), $format);

        return new Response($data);
    }
}
