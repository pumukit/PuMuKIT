<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class MultimediaObjectPicController extends Controller implements NewAdminControllerInterface
{
    /**
     * @Template("PumukitNewAdminBundle:Pic:create.html.twig")
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
     * @Template("PumukitNewAdminBundle:Pic:list.html.twig")
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
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template("PumukitNewAdminBundle:Pic:list.html.twig")
     */
    public function updateAction(MultimediaObject $multimediaObject, Request $request)
    {
        $isEventPoster = $request->get('is_event_poster', false);
        if (($url = $request->get('url')) || ($url = $request->get('picUrl'))) {
            $picService = $this->get('pumukitschema.mmspic');
            $multimediaObject = $picService->addPicUrl($multimediaObject, $url, true, $isEventPoster);
        }

        return [
            'resource' => $multimediaObject,
            'resource_name' => 'mms',
            'is_event_poster' => $isEventPoster,
        ];
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template("PumukitNewAdminBundle:Pic:upload.html.twig")
     */
    public function uploadAction(MultimediaObject $multimediaObject, Request $request)
    {
        $isEventPoster = $request->get('is_event_poster', false);
        try {
            if (0 === $request->files->count() && 0 === $request->request->count()) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if ($request->files->has('file')) {
                $picService = $this->get('pumukitschema.mmspic');
                $picService->addPicFile($multimediaObject, $request->files->get('file'), $isEventPoster);
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

        $repo = $this->get('doctrine_mongodb')
              ->getRepository(MultimediaObject::class);

        if (!$multimediaObject = $repo->findByPicId($picId)) {
            throw new NotFoundHttpException('Requested multimedia object does not exist');
        }

        $multimediaObject = $this->get('pumukitschema.mmspic')->removePicFromMultimediaObject($multimediaObject, $picId);

        return $this->redirect($this->generateUrl('pumukitnewadmin_mmspic_list', ['id' => $multimediaObject->getId(), 'is_event_poster' => $isEventPoster]));
    }

    /**
     * Up pic.
     */
    public function upAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->get('doctrine_mongodb')
              ->getRepository(MultimediaObject::class);

        if (!$multimediaObject = $repo->findByPicId($picId)) {
            throw new NotFoundHttpException('Requested multimedia object does not exist');
        }

        $multimediaObject->upPicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($multimediaObject);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_mmspic_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * Down pic.
     */
    public function downAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->get('doctrine_mongodb')
              ->getRepository(MultimediaObject::class);

        if (!$multimediaObject = $repo->findByPicId($picId)) {
            throw new NotFoundHttpException('Requested multimedia object does not exist');
        }

        $multimediaObject->downPicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($multimediaObject);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_mmspic_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:picstoaddlist.html.twig")
     */
    public function picstoaddlistAction(MultimediaObject $multimediaObject, Request $request)
    {
        $isEventPoster = $request->get('is_event_poster', false);
        $picService = $this->get('pumukitschema.mmspic');

        if ($request->get('page', null)) {
            $this->get('session')->set('admin/mmspic/page', $request->get('page', 1));
        }
        $page = intval($this->get('session')->get('admin/mmspic/page', 1));
        $limit = 12;

        $series = $multimediaObject->getSeries();

        $urlPics = $picService->getRecommendedPics($series);

        $total = intval(ceil(count($urlPics) / $limit));

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
     * @Template("PumukitNewAdminBundle:Pic:generate.html.twig")
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

            $picService = $this->get('pumukitschema.mmspic');
            $picService->addPicMem($multimediaObject, $data, $format);

            return new JsonResponse('done');
        } else {
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
    }

    /**
     * Get paginated pics.
     *
     * @param array $urlPics
     * @param int   $limit
     * @param int   $page
     *
     * @return Pagerfanta
     */
    private function getPaginatedPics($urlPics, $limit, $page)
    {
        $adapter = new ArrayAdapter($urlPics->toArray());
        $pics = new Pagerfanta($adapter);

        $pics
            ->setMaxPerPage($limit)
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage($page);

        return $pics;
    }
}
