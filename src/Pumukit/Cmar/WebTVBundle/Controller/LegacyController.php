<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class LegacyController extends Controller
{
    /**
     * @Route("/serial/index/id/{pumukit1id}")
     * {pumukit1id} matches series.properties("pumukit1id")
     */
    public function seriesAction($pumukit1id)
    {
        $dm = $this->get("doctrine_mongodb.odm.document_manager");
        $seriesRepo = $dm->getRepository("PumukitSchemaBundle:Series");

        $series = $seriesRepo->createQueryBuilder()
          ->field("properties.pumukit1id")->equals($pumukit1id)
          ->getQuery()->getSingleResult();

        if (!$series) {
            return new Response($this->render("PumukitWebTVBundle:Index:404notfound.html.twig", array()), 404);
        }

        return $this->redirect($this->generateUrl("pumukit_webtv_series_index", array("id" => $series->getId())));
    }

    /**
     * @Route("/{_locale}/video/{pumukit1id}.html")
     * {_locale} matches current locale
     * {pumukit1id} matches multimediaObject.properties("pumukit1id")
     */
    public function multimediaObjectAction($pumukit1id)
    {
        $dm = $this->get("doctrine_mongodb.odm.document_manager");
        $mmobjRepo = $dm->getRepository("PumukitSchemaBundle:MultimediaObject");

        $multimediaObject = $mmobjRepo->createQueryBuilder()
          ->field("properties.pumukit1id")->equals($pumukit1id)
          ->getQuery()->getSingleResult();

        if (!$multimediaObject) {
            return new Response($this->render("PumukitWebTVBundle:Index:404notfound.html.twig", array()), 404);
        }

        return $this->redirect($this->generateUrl("pumukit_webtv_multimediaobject_index", array("id" => $multimediaObject->getId())));
    }

    /**
     * @Route("/mmobj/index/file_id/{pumukit1id}")
     * {pumukit1id} matches the tag "pumukit1id:{pumukit1id}" in track.getTags()
     */
    public function trackAction($pumukit1id)
    {
        $dm = $this->get("doctrine_mongodb.odm.document_manager");
        $mmobjRepo = $dm->getRepository("PumukitSchemaBundle:MultimediaObject");

        $multimediaObject = $mmobjRepo->createQueryBuilder()
          ->field("tracks.tags")->equals(new \MongoRegex("/\bpumukit1id:".$pumukit1id."\b/i"))
          ->getQuery()->getSingleResult();

        if (!$multimediaObject) {
            return new Response($this->render("PumukitWebTVBundle:Index:404notfound.html.twig", array()), 404);
        }

        return $this->redirect($this->generateUrl("pumukit_webtv_multimediaobject_index", array("id" => $multimediaObject->getId())));
    }
}