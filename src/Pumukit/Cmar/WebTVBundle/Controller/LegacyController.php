<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
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
    public function seriesAction(Request $request)
    {
        $dm = $this->get("doctrine_mongodb.odm.document_manager");
        $seriesRepo = $dm->getRepository("PumukitSchemaBundle:Series");

        $mysqlid = $request->get("mysqlid");

        $series = $seriesRepo->createQueryBuilder()
          ->field("properties.mysqlid")->equals($mysqlid)
          ->getQuery()->getSingleResult();

        if (!$series) {
            return new Response($this->render("PumukitWebTVBundle:Index:404notfound.html.twig", array()), 404);
        }

        return $this->redirect($this->generateUrl("pumukit_webtv_series_index", array("id" => $series->getId())));
    }

    /**
     * @Route("/{locale}/video/{mysqlid}.html")
     * {locale} matches current locale
     * {mysqlid} matches multimediaObject.properties("mysqlid")
     */
    public function multimediaObjectAction(Request $request)
    {
        $dm = $this->get("doctrine_mongodb.odm.document_manager");
        $mmobjRepo = $dm->getRepository("PumukitSchemaBundle:MultimediaObject");

        $locale = $request->get("locale");
        $this->get('session')->set('_locale', $locale);

        $mysqlid = $request->get("mysqlid");

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
    public function trackAction(Request $request)
    {
        $dm = $this->get("doctrine_mongodb.odm.document_manager");
        $mmobjRepo = $dm->getRepository("PumukitSchemaBundle:MultimediaObject");

        $mysqlid = $request->get("mysqlid");

        $multimediaObject = $mmobjRepo->createQueryBuilder()
          ->field("tracks.tags")->equals(new \MongoRegex("/\bmysqlid:".$mysqlid."\b/i"))
          ->getQuery()->getSingleResult();

        if (!$multimediaObject) {
            return new Response($this->render("PumukitWebTVBundle:Index:404notfound.html.twig", array()), 404);
        }

        return $this->redirect($this->generateUrl("pumukit_webtv_multimediaobject_index", array("id" => $multimediaObject->getId())));
    }
}