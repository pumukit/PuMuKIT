<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class LegacyController extends Controller implements WebTVController
{
    /**
     * @Route("/serial/index/id/{pumukit1id}.html")
     * @Route("/serial/index/id/{pumukit1id}")
     * @Route("/{_locale}/serial/index/id/{pumukit1id}.html")
     * @Route("/{_locale}/serial/index/id/{pumukit1id}")
     * @Route("/{_locale}/serial/{pumukit1id}.html")
     * @Route("/{_locale}/serial/{pumukit1id}")
     *
     * Parameters:
     * - {_locale} matches the current locale
     * - {pumukit1id} matches series.properties("pumukit1id")
     */
    public function seriesAction($pumukit1id)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $seriesRepo = $dm->getRepository('PumukitSchemaBundle:Series');

        $series = $seriesRepo->createQueryBuilder()
          ->field('properties.pumukit1id')->equals($pumukit1id)
          ->getQuery()->getSingleResult();

        if (!$series) {
            throw $this->createNotFoundException();
        }

        return $this->redirect($this->generateUrl('pumukit_webtv_series_index', array('id' => $series->getId())));
    }

    /**
     * @Route("/{_locale}/video/{pumukit1id}.html", defaults={"filter": false})
     * @Route("/{_locale}/video/{pumukit1id}", defaults={"filter": false})
     * @Route("/video/{pumukit1id}", requirements={
     *     "pumukit1id": "\d+"
     * }, defaults={"filter": false})
     * @Route("/video/index/id/{pumukit1id}.html", defaults={"filter": false})
     * @Route("/video/index/id/{pumukit1id}", defaults={"filter": false})
     *
     * Parameters:
     * - {_locale} matches current locale
     * - {pumukit1id} matches multimediaObject.properties("pumukit1id")
     */
    public function multimediaObjectAction($pumukit1id)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');

        $multimediaObject = $mmobjRepo->createQueryBuilder()
          ->field('properties.pumukit1id')->equals($pumukit1id)
          ->field('status')->gte(MultimediaObject::STATUS_PUBLISHED)
          ->getQuery()->getSingleResult();

        if (!$multimediaObject) {
            throw $this->createNotFoundException();
        }
        if ($multimediaObject->getStatus() == MultimediaObject::STATUS_HIDE) {
            return $this->redirect($this->generateUrl('pumukit_webtv_multimediaobject_magicindex', array('secret' => $multimediaObject->getSecret())));
        } else {
            return $this->redirect($this->generateUrl('pumukit_webtv_multimediaobject_index', array('id' => $multimediaObject->getId())));
        }
    }

    /**
     * @Route("/{_locale}/video/iframe/{pumukit1id}.html")
     *
     * Parameters:
     * - {_locale} matches the current locale
     * - {pumukit1id} matches multimediaObject.properties("pumukit1id")
     */
    public function multimediaObjectIframeAction($pumukit1id)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');

        $multimediaObject = $mmobjRepo->createQueryBuilder()
          ->field('properties.pumukit1id')->equals($pumukit1id)
          ->getQuery()->getSingleResult();

        if (!$multimediaObject) {
            throw $this->createNotFoundException();
        }

        return $this->redirect($this->generateUrl('pumukit_webtv_multimediaobject_iframe', array('id' => $multimediaObject->getId())));
    }

    /**
     * @Route("/file/{pumukit1id}")
     * @Route("/{_locale}/file/{pumukit1id}.html")
     * @Route("/{_locale}/file/{pumukit1id}")
     *
     * Parameters:
     * - {_locale} matches the current locale
     * - {pumukit1id} matches the tag "pumukit1id:{pumukit1id}" in track.getTags()
     */
    public function trackAction($pumukit1id)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');

        $multimediaObject = $mmobjRepo->createQueryBuilder()
          ->field('tracks.tags')->equals(new \MongoRegex("/\bpumukit1id:".$pumukit1id."\b/i"))
          ->getQuery()->getSingleResult();

        if (!$multimediaObject) {
            throw $this->createNotFoundException();
        }

        return $this->redirect($this->generateUrl('pumukit_webtv_multimediaobject_index', array('id' => $multimediaObject->getId())));
    }

    /**
     * @Route("/serial/index/hash/{hash}")
     *
     * Parameters:
     * - {hash} matches series.properties("pumukit1magic")
     */
    public function magicAction($hash)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $seriesRepo = $dm->getRepository('PumukitSchemaBundle:Series');

        $series = $seriesRepo->createQueryBuilder()
          ->field('properties.pumukit1magic')->equals($hash)
          ->getQuery()->getSingleResult();

        if (null == $series) {
            throw $this->createNotFoundException();
        }

        return $this->redirect($this->generateUrl('pumukit_webtv_series_magicindex', array('secret' => $series->getSecret())));
    }

    /**
     * @Route("/directo.html")
     */
    public function directoAction()
    {
        return $this->redirect($this->generateUrl('pumukit_live', array()));
    }
}
