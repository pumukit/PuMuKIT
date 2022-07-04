<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class MultimediaObjectPicController extends AbstractController implements NewAdminControllerInterface
{
    /** @var DocumentManager */
    private $documentManager;
    /** @var PaginationService */
    private $paginationService;
    /** @var SessionInterface */
    private $session;
    /** @var MultimediaObjectPicService */
    private $multimediaObjectPicService;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        SessionInterface $session,
        MultimediaObjectPicService $multimediaObjectPicService
    ) {
        $this->documentManager = $documentManager;
        $this->paginationService = $paginationService;
        $this->session = $session;
        $this->multimediaObjectPicService = $multimediaObjectPicService;
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/create.html.twig")
     */
    public function createAction(MultimediaObject $multimediaObject, Request $request)
    {
        $isEventPoster = $request->get('is_event_poster', false);

        return [
            'resource' => $multimediaObject,
            'resource_name' => 'mms',
            'is_event_poster' => $isEventPoster,
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/list.html.twig")
     */
    public function listAction(MultimediaObject $multimediaObject, Request $request)
    {
        $isEventPoster = $request->get('is_event_poster', false);

        return [
            'resource' => $multimediaObject,
            'resource_name' => 'mms',
            'is_event_poster' => $isEventPoster,
        ];
    }

    /**
     * Assign a picture from an url or from an existing one to the multimedia object.
     *
     * @Template("@PumukitNewAdmin/Pic/list.html.twig")
     */
    public function updateAction(MultimediaObject $multimediaObject, Request $request)
    {
        $isEventPoster = $request->get('is_event_poster', false);
        if (($url = $request->get('url')) || ($url = $request->get('picUrl'))) {
            $multimediaObject = $this->multimediaObjectPicService->addPicUrl($multimediaObject, $url, true, $isEventPoster);
        }

        return [
            'resource' => $multimediaObject,
            'resource_name' => 'mms',
            'is_event_poster' => $isEventPoster,
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/upload.html.twig")
     */
    public function uploadAction(MultimediaObject $multimediaObject, Request $request)
    {
        $isEventPoster = $request->get('is_event_poster', false);

        try {
            if (0 === $request->files->count() && 0 === $request->request->count()) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if ($request->files->has('file')) {
                $this->multimediaObjectPicService->addPicFile($multimediaObject, $request->files->get('file'), $isEventPoster);
            }
        } catch (\Exception $e) {
            return [
                'resource' => $multimediaObject,
                'resource_name' => 'mms',
                'uploaded' => 'failed',
                'message' => $e->getMessage(),
                'isBanner' => false,
                'is_event_poster' => $isEventPoster,
            ];
        }

        return [
            'resource' => $multimediaObject,
            'resource_name' => 'mms',
            'uploaded' => 'success',
            'message' => 'New Pic added.',
            'isBanner' => false,
            'is_event_poster' => $isEventPoster,
        ];
    }

    /**
     * Delete pic.
     */
    public function deleteAction(Request $request)
    {
        $isEventPoster = $request->get('is_event_poster', false);
        $picId = $request->get('id');

        $repo = $this->documentManager->getRepository(MultimediaObject::class);

        if (!$multimediaObject = $repo->findByPicId($picId)) {
            throw new NotFoundHttpException('Requested multimedia object does not exist');
        }

        $multimediaObject = $this->multimediaObjectPicService->removePicFromMultimediaObject($multimediaObject, $picId);

        return $this->redirect($this->generateUrl('pumukitnewadmin_mmspic_list', ['id' => $multimediaObject->getId(), 'is_event_poster' => $isEventPoster]));
    }

    /**
     * Up pic.
     */
    public function upAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->documentManager->getRepository(MultimediaObject::class);

        $multimediaObject = $repo->findByPicId($picId);
        if (!$multimediaObject instanceof MultimediaObject) {
            throw new NotFoundHttpException('Requested multimedia object does not exist');
        }

        $multimediaObject->upPicById($picId);

        $this->documentManager->persist($multimediaObject);
        $this->documentManager->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_mmspic_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * Down pic.
     */
    public function downAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->documentManager->getRepository(MultimediaObject::class);

        $multimediaObject = $repo->findByPicId($picId);
        if (!$multimediaObject instanceof MultimediaObject) {
            throw new NotFoundHttpException('Requested multimedia object does not exist');
        }

        $multimediaObject->downPicById($picId);

        $this->documentManager->persist($multimediaObject);
        $this->documentManager->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_mmspic_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/picstoaddlist.html.twig")
     */
    public function picstoaddlistAction(MultimediaObject $multimediaObject, Request $request)
    {
        $isEventPoster = $request->get('is_event_poster', false);

        if ($request->get('page', null)) {
            $this->session->set('admin/mmspic/page', $request->get('page', 1));
        }
        $page = (int) ($this->session->get('admin/mmspic/page', 1));
        $limit = 12;

        $series = $multimediaObject->getSeries();

        $urlPics = $this->multimediaObjectPicService->getRecommendedPics($series);

        $total = (int) (ceil(count($urlPics) / $limit));

        $pics = $this->getPaginatedPics($urlPics, $limit, $page);

        return [
            'resource' => $multimediaObject,
            'resource_name' => 'mms',
            'pics' => $pics,
            'page' => $page,
            'total' => $total,
            'is_event_poster' => $isEventPoster,
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/generate.html.twig")
     */
    public function generateAction(MultimediaObject $multimediaObject, Request $request)
    {
        if ($request->isMethod('POST')) {
            if (!$request->request->has('img')) {
                throw new NotFoundHttpException('No exist a img paramater');
            }

            $base_64 = $request->request->get('img');
            $decodedData = substr($base_64, 22, strlen($base_64));
            $format = substr($base_64, strpos($base_64, '/') + 1, strpos($base_64, ';') - 1 - strpos($base_64, '/'));

            $data = base64_decode($decodedData);

            $this->multimediaObjectPicService->addPicMem($multimediaObject, $data, $format);

            return new JsonResponse('done');
        }
        $track = $request->query->has('track_id') ?
                   $multimediaObject->getTrackById($request->query->get('track_id')) :
                   $multimediaObject->getDisplayTrack();

        if (!$track || $track->isOnlyAudio()) {
            throw new NotFoundHttpException("Requested multimedia object doesn't have a public track");
        }

        return [
            'mm' => $multimediaObject,
            'track' => $track,
        ];
    }

    private function getPaginatedPics($urlPics, $limit, $page)
    {
        return $this->paginationService->createArrayAdapter($urlPics, $page, $limit);
    }
}
