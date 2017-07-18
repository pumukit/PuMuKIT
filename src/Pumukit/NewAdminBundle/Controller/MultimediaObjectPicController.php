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
class MultimediaObjectPicController extends Controller implements NewAdminController
{
    /**
     * @Template("PumukitNewAdminBundle:Pic:create.html.twig")
     */
    public function createAction(MultimediaObject $multimediaObject, Request $request)
    {
        return array(
                     'resource' => $multimediaObject,
                     'resource_name' => 'mms',
                     );
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:list.html.twig")
     */
    public function listAction(MultimediaObject $multimediaObject)
    {
        return array(
                     'resource' => $multimediaObject,
                     'resource_name' => 'mms',
                     );
    }

    /**
     * Assign a picture from an url or from an existing one to the multimedia object.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template("PumukitNewAdminBundle:Pic:list.html.twig")
     */
    public function updateAction(MultimediaObject $multimediaObject, Request $request)
    {
        if (($url = $request->get('url')) || ($url = $request->get('picUrl'))) {
            $picService = $this->get('pumukitschema.mmspic');
            $multimediaObject = $picService->addPicUrl($multimediaObject, $url);
        }

        return array(
                     'resource' => $multimediaObject,
                     'resource_name' => 'mms',
                     );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template("PumukitNewAdminBundle:Pic:upload.html.twig")
     */
    public function uploadAction(MultimediaObject $multimediaObject, Request $request)
    {
        try {
            if (empty($_FILES) && empty($_POST)) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if ($request->files->has('file')) {
                $picService = $this->get('pumukitschema.mmspic');
                $media = $picService->addPicFile($multimediaObject, $request->files->get('file'));
            }
        } catch (\Exception $e) {
            return array(
                         'resource' => $multimediaObject,
                         'resource_name' => 'mms',
                         'uploaded' => 'failed',
                         'message' => $e->getMessage(),
                         'isBanner' => false,
                         );
        }

        return array(
                     'resource' => $multimediaObject,
                     'resource_name' => 'mms',
                     'uploaded' => 'success',
                     'message' => 'New Pic added.',
                     'isBanner' => false,
                     );
    }

    /**
     * Delete pic.
     */
    public function deleteAction(Request $request)
    {
        $picId = $this->getRequest()->get('id');

        $repo = $this->get('doctrine_mongodb')
      ->getRepository('PumukitSchemaBundle:MultimediaObject');

        if (!$multimediaObject = $repo->findByPicId($picId)) {
            throw new NotFoundHttpException('Requested multimedia object does not exist');
        }

        $multimediaObject = $this->get('pumukitschema.mmspic')->removePicFromMultimediaObject($multimediaObject, $picId);

        return $this->redirect($this->generateUrl('pumukitnewadmin_mmspic_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * Up pic.
     */
    public function upAction(Request $request)
    {
        $picId = $this->getRequest()->get('id');

        $repo = $this->get('doctrine_mongodb')
      ->getRepository('PumukitSchemaBundle:MultimediaObject');

        if (!$multimediaObject = $repo->findByPicId($picId)) {
            throw new NotFoundHttpException('Requested multimedia object does not exist');
        }

        $multimediaObject->upPicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($multimediaObject);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_mmspic_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * Down pic.
     */
    public function downAction(Request $request)
    {
        $picId = $this->getRequest()->get('id');

        $repo = $this->get('doctrine_mongodb')
      ->getRepository('PumukitSchemaBundle:MultimediaObject');

        if (!$multimediaObject = $repo->findByPicId($picId)) {
            throw new NotFoundHttpException('Requested multimedia object does not exist');
        }

        $multimediaObject->downPicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($multimediaObject);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_mmspic_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:picstoaddlist.html.twig")
     */
    public function picstoaddlistAction(MultimediaObject $multimediaObject, Request $request)
    {
        $picService = $this->get('pumukitschema.mmspic');

        // TODO search in picservice according to page (in criteria)
        if ($request->get('page', null)) {
            $this->get('session')->set('admin/mmspic/page', $request->get('page', 1));
        }
        $page = intval($this->get('session')->get('admin/mmspic/page', 1));
        $limit = 12;

        $series = $multimediaObject->getSeries();

        $urlPics = $picService->getRecommendedPics($series);

        $total = intval(ceil(count($urlPics) / $limit));

        $pics = $this->getPaginatedPics($urlPics, $limit, $page);

        return array(
                     'resource' => $multimediaObject,
                     'resource_name' => 'mms',
                     'pics' => $pics,
                     'page' => $page,
                     'total' => $total,
                     );
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

            return array(
                'mm' => $multimediaObject,
                'track' => $track,
            );
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
