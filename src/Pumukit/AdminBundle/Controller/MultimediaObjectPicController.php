<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectPicController extends Controller
{
    /**
     *
     * @Template("PumukitAdminBundle:Pic:create.html.twig")
     */
    public function createAction(MultimediaObject $multimediaObject, Request $request)
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
                     'total' => $total
                     );
    }

    /**
     *
     * @Template("PumukitAdminBundle:Pic:list.html.twig")
     */
    public function listAction(MultimediaObject $multimediaObject)
    {
        return array(
                     'resource' => $multimediaObject,
                     'resource_name' => 'mms',
                     );
    }

    /**
     * Assign a picture from an url or from an existing one to the multimedia object
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template("PumukitAdminBundle:Pic:list.html.twig")
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
     * @Template("PumukitAdminBundle:Pic:upload.html.twig")
     */
    public function uploadAction(MultimediaObject $multimediaObject, Request $request)
    {
        if ($request->files->has("file")) {
            $picService = $this->get('pumukitschema.mmspic');
            $media = $picService->addPicFile($multimediaObject, $request->files->get("file"));
        }
        
        return array(
                     'resource' => $multimediaObject,
                     'resource_name' => 'mms',
                     );
    }
  
    /**
     * Delete pic
     */
    public function deleteAction(Request $request)
    {
        $picId = $this->getRequest()->get('id');

        $repo = $this->get('doctrine_mongodb')
      ->getRepository('PumukitSchemaBundle:MultimediaObject');

        if (!$multimediaObject = $repo->findByPicId($picId)) {
            throw new NotFoundHttpException('Requested multimedia object does not exist');
        }

        $multimediaObject->removePicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($multimediaObject);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitadmin_mmspic_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * Up pic
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

        return $this->redirect($this->generateUrl('pumukitadmin_mmspic_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * Down pic
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

        return $this->redirect($this->generateUrl('pumukitadmin_mmspic_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * Get paginated pics
     *
     * @param array $urlPics
     * @param int $limit
     * @param int $page
     * @return Pagerfanta
     */
    private function getPaginatedPics($urlPics, $limit, $page)
    {
        $adapter = new ArrayAdapter($urlPics->toArray());
        $pics = new Pagerfanta($adapter);

        $pics
          ->setCurrentPage($page, true, true)
          ->setMaxPerPage($limit);

        return $pics;
    }
}
