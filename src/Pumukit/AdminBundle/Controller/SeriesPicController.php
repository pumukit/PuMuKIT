<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;

class SeriesPicController extends Controller
{
    /**
     *
     * @Template("PumukitAdminBundle:Pic:create.html.twig")
     */
    public function createAction(Series $series, Request $request)
    {
      $picService = $this->get('pumukitschema.seriespic');

      // TODO search in picservice according to page (in criteria)
      if ($request->get('page', null)) {
          $this->get('session')->set('admin/seriespic/page', $request->get('page', 1));
      }
      $page = intval($this->get('session')->get('admin/seriespic/page', 1));
      $limit = 12;

      $urlPics = $picService->getRecommendedPics($series);

      $total = intval(ceil(count($urlPics) / $limit));

      $pics = $this->getPaginatedPics($urlPics, $limit, $page);

      return array(
                   'resource' => $series,
                   'resource_name' => 'series',
                   'pics' => $pics,
                   'page' => $page,
                   'total' => $total
                   );
    }

    /**
     *
     * @Template("PumukitAdminBundle:Pic:list.html.twig")
     */
    public function listAction(Series $series)
    {
      return array(
                   'resource' => $series,
                   'resource_name' => 'series',
                   );
    }

    /**
     * Assign a picture from an url or from an existing one to the series
     *
     * @Template("PumukitAdminBundle:Pic:list.html.twig")
     */
    public function updateAction(Series $series, Request $request)
    {
      if (($url = $request->get('url')) || ($url = $request->get('picUrl'))) {
        $picService = $this->get('pumukitschema.seriespic');
        $series = $picService->addPicUrl($series, $url);
      }

      return array(
                   'resource' => $series,
                   'resource_name' => 'series',
                   );
    }

    /**
     *
     * @Template("PumukitAdminBundle:Pic:upload.html.twig")
     */
    public function uploadAction(Series $series, Request $request)
    {
      if ($request->files->has("file")) {
        $picService = $this->get('pumukitschema.seriespic');
        $media = $picService->addPicFile($series, $request->files->get("file"));
      }

      return array(
                   'resource' => $series,
                   'resource_name' => 'series',
                   );
    }

    /**
     * Delete pic
     */
    public function deleteAction(Request $request)
    {
        $picId = $this->getRequest()->get('id');

        $repo = $this->get('doctrine_mongodb')
      ->getRepository('PumukitSchemaBundle:Series');

        if (!$series = $repo->findByPicId($picId)) {
            throw new NotFoundHttpException('Requested series does not exist');
        }

        $series->removePicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($series);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitadmin_seriespic_list', array('id' => $series->getId())));
    }

    /**
     * Up pic
     */
    public function upAction(Request $request)
    {
        $picId = $this->getRequest()->get('id');

        $repo = $this->get('doctrine_mongodb')
      ->getRepository('PumukitSchemaBundle:Series');

        if (!$series = $repo->findByPicId($picId)) {
            throw new NotFoundHttpException('Requested series does not exist');
        }

        $series->upPicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($series);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitadmin_seriespic_list', array('id' => $series->getId())));
    }

    /**
     * Down pic
     */
    public function downAction(Request $request)
    {
        $picId = $this->getRequest()->get('id');

        $repo = $this->get('doctrine_mongodb')
      ->getRepository('PumukitSchemaBundle:Series');

        if (!$series = $repo->findByPicId($picId)) {
            throw new NotFoundHttpException('Requested series does not exist');
        }

        $series->downPicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($series);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitadmin_seriespic_list', array('id' => $series->getId())));
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
