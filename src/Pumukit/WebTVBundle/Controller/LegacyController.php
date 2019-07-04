<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LegacyController.
 */
class LegacyController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/serial/index/id/{pumukit1id}.html")
     * @Route("/serial/index/id/{pumukit1id}")
     * @Route("/{_locale}/serial/index/id/{pumukit1id}.html", requirements={"_locale"=".."})
     * @Route("/{_locale}/serial/index/id/{pumukit1id}", requirements={"_locale"=".."})
     * @Route("/{_locale}/serial/{pumukit1id}.html", requirements={"_locale"=".."})
     * @Route("/{_locale}/serial/{pumukit1id}", requirements={"_locale"=".."})
     * @Route("/index.php/{_locale}/serial/{pumukit1id}.html")
     * @Route("/index.php/{_locale}/serial/{pumukit1id}")
     * Parameters:
     * - {_locale} matches the current locale
     * - {pumukit1id} matches series.properties("pumukit1id")
     *
     * @param $pumukit1id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function seriesAction($pumukit1id)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $seriesRepo = $dm->getRepository(Series::class);

        $series = $seriesRepo->createQueryBuilder()
            ->field('properties.pumukit1id')->equals($pumukit1id)
            ->getQuery()->getSingleResult();

        if (!$series) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('pumukit_webtv_series_index', ['id' => $series->getId()], Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * @Route("/{_locale}/video/{pumukit1id}.html", defaults={"filter": false}, requirements={"_locale"=".."})
     * @Route("/{_locale}/video/{pumukit1id}", defaults={"filter": false}, requirements={"_locale"=".."})
     * @Route("/{_locale}/video/mm/{pumukit1id}.html", defaults={"filter": false})
     * @Route("/{_locale}/video/mm/{pumukit1id}", defaults={"filter": false})
     * @Route("/video/{pumukit1id}.html", requirements={
     *     "pumukit1id": "\d+"
     * }, defaults={"filter": false})
     * @Route("/video/{pumukit1id}", requirements={
     *     "pumukit1id": "\d+"
     * }, defaults={"filter": false})
     * @Route("/mmobj/index/id/{pumukit1id}.html", defaults={"filter": false})
     * @Route("/mmobj/index/id/{pumukit1id}", requirements={
     *     "pumukit1id": "\d+"
     * }, defaults={"filter": false})
     * @Route("/index.php/{_locale}/video/{pumukit1id}.html", defaults={"filter": false}, requirements={"_locale"=".."})
     * @Route("/index.php/{_locale}/video/{pumukit1id}", defaults={"filter": false}, requirements={"_locale"=".."})
     * @Route("/index.php/video/{pumukit1id}", requirements={
     *     "pumukit1id": "\d+"
     * }, defaults={"filter": false})
     * @Route("/video/index/id/{pumukit1id}.html", defaults={"filter": false})
     * @Route("/video/index/id/{pumukit1id}", defaults={"filter": false})
     * @Route("/index.php/video/index/id/{pumukit1id}.html", defaults={"filter": false})
     * @Route("/index.php/video/index/id/{pumukit1id}", defaults={"filter": false})
     * Parameters:
     * - {_locale} matches current locale
     * - {pumukit1id} matches multimediaObject.properties("pumukit1id")
     *
     * @param $pumukit1id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function multimediaObjectAction($pumukit1id)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository(MultimediaObject::class);

        $multimediaObject = $mmobjRepo->createQueryBuilder()
            ->field('properties.pumukit1id')->equals($pumukit1id)
            ->field('status')->gte(MultimediaObject::STATUS_PUBLISHED)
            ->getQuery()->getSingleResult();

        if (!$multimediaObject) {
            throw $this->createNotFoundException();
        }
        if (MultimediaObject::STATUS_HIDDEN == $multimediaObject->getStatus()) {
            return $this->redirectToRoute(
                'pumukit_webtv_multimediaobject_magicindex',
                ['secret' => $multimediaObject->getSecret()],
                Response::HTTP_MOVED_PERMANENTLY
            );
        }

        return $this->redirectToRoute(
            'pumukit_webtv_multimediaobject_index',
            ['id' => $multimediaObject->getId()],
            Response::HTTP_MOVED_PERMANENTLY
            );
    }

    /**
     * @Route("/{_locale}/mmobj/iframe/id/{pumukit1id}", defaults={"filter": false}, requirements={"_locale"=".."})
     * @Route("/{_locale}/video/iframe/{pumukit1id}.html", defaults={"filter": false}, requirements={"_locale"=".."})
     * @Route("/index.php/{_locale}/video/iframe/{pumukit1id}.html", defaults={"filter": false}, requirements={"_locale"=".."})
     *
     * Parameters:
     * - {_locale} matches the current locale
     * - {pumukit1id} matches multimediaObject.properties("pumukit1id")
     *
     * @param $pumukit1id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function multimediaObjectIframeAction($pumukit1id)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository(MultimediaObject::class);

        $multimediaObject = $mmobjRepo->createQueryBuilder()
            ->field('properties.pumukit1id')->equals($pumukit1id)
            ->getQuery()->getSingleResult();

        if (!$multimediaObject) {
            throw $this->createNotFoundException();
        }

        if ($multimediaObject->isHidden()) {
            return $this->redirectToRoute('pumukit_webtv_multimediaobject_magiciframe', ['secret' => $multimediaObject->getSecret()], Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->redirectToRoute('pumukit_webtv_multimediaobject_iframe', ['id' => $multimediaObject->getId()], Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * @Route("/file/{pumukit1id}")
     * @Route("/{_locale}/file/{pumukit1id}.html", requirements={"_locale"=".."})
     * @Route("/{_locale}/file/{pumukit1id}", requirements={"_locale"=".."})
     * Parameters:
     * - {_locale} matches the current locale
     * - {pumukit1id} matches the tag "pumukit1id:{pumukit1id}" in track.getTags()
     *
     * @param $pumukit1id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function trackAction($pumukit1id)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository(MultimediaObject::class);

        $multimediaObject = $mmobjRepo->createQueryBuilder()
            ->field('tracks.tags')->equals(new \MongoRegex('/\\bpumukit1id:'.$pumukit1id.'\\b/i'))
            ->getQuery()->getSingleResult();

        if (!$multimediaObject) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('pumukit_webtv_multimediaobject_index', ['id' => $multimediaObject->getId()], Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * @Route("/serial/index/hash/{hash}")
     * Parameters:
     * - {hash} matches series.properties("pumukit1magic")
     *
     * @param $hash
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function magicAction($hash)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $seriesRepo = $dm->getRepository(Series::class);

        $series = $seriesRepo->createQueryBuilder()
            ->field('properties.pumukit1magic')->equals($hash)
            ->getQuery()->getSingleResult();

        if (null === $series) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('pumukit_webtv_series_magicindex', ['secret' => $series->getSecret()], Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * @Route("/directo.html")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function directoAction()
    {
        return $this->redirectToRoute('pumukit_live', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
