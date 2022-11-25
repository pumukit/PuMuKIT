<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LegacyController extends AbstractController implements WebTVControllerInterface
{
    /** @var DocumentManager */
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

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
     */
    public function seriesAction(string $pumukit1id)
    {
        $seriesRepo = $this->documentManager->getRepository(Series::class);

        $series = $seriesRepo->createQueryBuilder()
            ->field('properties.pumukit1id')->equals($pumukit1id)
            ->getQuery()->getSingleResult();

        if (!$series instanceof Series) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('pumukit_webtv_series_index', ['id' => $series->getId()], Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * @Route("/{_locale}/video/{pumukit1id}.html", defaults={"filter"=false}, requirements={"_locale"=".."})
     * @Route("/{_locale}/video/{pumukit1id}", defaults={"filter"=false}, requirements={"_locale"=".."})
     * @Route("/{_locale}/video/mm/{pumukit1id}.html", defaults={"filter"=false})
     * @Route("/{_locale}/video/mm/{pumukit1id}", defaults={"filter"=false})
     * @Route("/video/{pumukit1id}.html", requirements={"pumukit1id"="[0-9]{6}"}, defaults={"filter"=false})
     * @Route("/video/{pumukit1id}", requirements={"pumukit1id"="[0-9]{6}"}, defaults={"filter"=false})
     * @Route("/mmobj/index/id/{pumukit1id}.html", defaults={"filter"=false})
     * @Route("/mmobj/index/id/{pumukit1id}", requirements={"pumukit1id"="[0-9]{6}"}, defaults={"filter"=false})
     * @Route("/index.php/{_locale}/video/{pumukit1id}.html", defaults={"filter"=false}, requirements={"_locale"=".."})
     * @Route("/index.php/{_locale}/video/{pumukit1id}", defaults={"filter"=false}, requirements={"_locale"=".."})
     * @Route("/index.php/video/{pumukit1id}", requirements={"pumukit1id"="[0-9]{6}"}, defaults={"filter"=false})
     * @Route("/video/index/id/{pumukit1id}.html", defaults={"filter"=false})
     * @Route("/video/index/id/{pumukit1id}", defaults={"filter"=false})
     * @Route("/index.php/video/index/id/{pumukit1id}.html", defaults={"filter"=false})
     * @Route("/index.php/video/index/id/{pumukit1id}", defaults={"filter"=false})
     */
    public function multimediaObjectAction(string $pumukit1id)
    {
        $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);

        $multimediaObject = $mmobjRepo->createQueryBuilder()
            ->field('properties.pumukit1id')->equals($pumukit1id)
            ->field('status')->gte(MultimediaObject::STATUS_PUBLISHED)
            ->getQuery()->getSingleResult();

        if (!$multimediaObject instanceof MultimediaObject) {
            throw $this->createNotFoundException();
        }
        if (MultimediaObject::STATUS_HIDDEN === $multimediaObject->getStatus()) {
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
     * @Route("/{_locale}/mmobj/iframe/id/{pumukit1id}", defaults={"filter"=false}, requirements={"_locale"=".."})
     * @Route("/{_locale}/video/iframe/{pumukit1id}.html", defaults={"filter"=false}, requirements={"_locale"=".."})
     * @Route("/index.php/{_locale}/video/iframe/{pumukit1id}.html", defaults={"filter"=false}, requirements={"_locale"=".."})
     */
    public function multimediaObjectIframeAction(string $pumukit1id)
    {
        $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);

        $multimediaObject = $mmobjRepo->createQueryBuilder()
            ->field('properties.pumukit1id')->equals($pumukit1id)
            ->getQuery()->getSingleResult();

        if (!$multimediaObject instanceof MultimediaObject) {
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
     */
    public function trackAction(string $pumukit1id)
    {
        $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);

        $multimediaObject = $mmobjRepo->createQueryBuilder()
            ->field('tracks.tags')->equals(new Regex('pumukit1id:'.$pumukit1id, 'i'))
            ->getQuery()->getSingleResult();

        if (!$multimediaObject instanceof MultimediaObject) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('pumukit_webtv_multimediaobject_index', ['id' => $multimediaObject->getId()], Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * @Route("/serial/index/hash/{hash}")
     * Parameters:
     * - {hash} matches series.properties("pumukit1magic")
     */
    public function magicAction(string $hash)
    {
        $seriesRepo = $this->documentManager->getRepository(Series::class);

        $series = $seriesRepo->createQueryBuilder()
            ->field('properties.pumukit1magic')->equals($hash)
            ->getQuery()->getSingleResult();

        if (!$series instanceof Series) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('pumukit_webtv_series_magicindex', ['secret' => $series->getSecret()], Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * @Route("/directo.html")
     */
    public function directoAction()
    {
        return $this->redirectToRoute('pumukit_live', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
