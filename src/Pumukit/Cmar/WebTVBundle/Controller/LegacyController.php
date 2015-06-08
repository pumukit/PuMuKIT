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
     * @Route("/serial/index/id/{mysqlid}")
     * {mysqlid} matches series.properties("mysqlid")
     */
    public function seriesAction($mysqlid)
    {
        $dm = $this->get("doctrine_mongodb.odm.document_manager");
        $seriesRepo = $dm->getRepository("PumukitSchemaBundle:Series");

        $series = $seriesRepo->createQueryBuilder()
          ->field("properties.mysqlid")->equals($mysqlid)
          ->getQuery()->getSingleResult();

        if (!$series) {
            return new Response($this->render("PumukitWebTVBundle:Index:404notfound.html.twig", array()), 404);
        }

        return $this->redirect($this->generateUrl("pumukit_webtv_series_index", array("id" => $series->getId())));
    }

    /**
     * @Route("/{_locale}/video/{mysqlid}.html")
     * {_locale} matches current locale
     * {mysqlid} matches multimediaObject.properties("mysqlid")
     */
    public function multimediaObjectAction($mysqlid)
    {
        $dm = $this->get("doctrine_mongodb.odm.document_manager");
        $mmobjRepo = $dm->getRepository("PumukitSchemaBundle:MultimediaObject");

        $multimediaObject = $mmobjRepo->createQueryBuilder()
          ->field("properties.mysqlid")->equals($mysqlid)
          ->getQuery()->getSingleResult();

        if (!$multimediaObject) {
            return new Response($this->render("PumukitWebTVBundle:Index:404notfound.html.twig", array()), 404);
        }

        return $this->redirect($this->generateUrl("pumukit_webtv_multimediaobject_index", array("id" => $multimediaObject->getId())));
    }

    /**
     * @Route("/mmobj/index/file_id/{mysqlid}")
     * {mysqlid} matches the tag "mysqlid:{mysqlid}" in track.getTags()
     */
    public function trackAction($mysqlid)
    {
        $dm = $this->get("doctrine_mongodb.odm.document_manager");
        $mmobjRepo = $dm->getRepository("PumukitSchemaBundle:MultimediaObject");

        $multimediaObject = $mmobjRepo->createQueryBuilder()
          ->field("tracks.tags")->equals(new \MongoRegex("/\bmysqlid:".$mysqlid."\b/i"))
          ->getQuery()->getSingleResult();

        if (!$multimediaObject) {
            return new Response($this->render("PumukitWebTVBundle:Index:404notfound.html.twig", array()), 404);
        }

        return $this->redirect($this->generateUrl("pumukit_webtv_multimediaobject_index", array("id" => $multimediaObject->getId())));
    }
}