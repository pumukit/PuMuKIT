<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Adapter\DoctrineSelectableAdapter;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Adapter\MongoAdapter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectMetaType;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectPubType;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("is_granted('ROLE_ACCESS_EDIT_PLAYLIST')")
 */
class PlaylistMultimediaObjectController extends MultimediaObjectController
{
    /**
     * Overwrite to search criteria with date
     * @Template
     */
    public function indexAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $sessionId = $this->get('session')->get('admin/playlist/id', null);
        $series = $factoryService->findSeriesById($request->query->get('id'), $sessionId);
        if(!$series) throw $this->createNotFoundException();

        $this->get('session')->set('admin/playlist/id', $series->getId());

        if($request->query->has('mmid')) {
            $this->get('session')->set('admin/playlistmms/id', $request->query->get('mmid'));
        }

        $mms = $this->getPlaylistMmobjs($series);
        return array(
                     'playlist' => $series,
                     'mms' => $mms
                     );
    }


    public function listAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $sessionId = $this->get('session')->get('admin/playlist/id', null);
        $series = $factoryService->findSeriesById($request->query->get('id'), $sessionId);
        if(!$series) throw $this->createNotFoundException();

        $this->get('session')->set('admin/playlist/id', $series->getId());

        if($request->query->has('mmid')) {
            $this->get('session')->set('admin/playlistmms/id', $request->query->get('mmid'));
        }

        $mms = $this->getPlaylistMmobjs($series);
        return array(
                     'playlist' => $series,
                     'mms' => $mms
                     );
    }

    //TODO: This? Or getResources(like in PlaylistController?)
    protected function getPlaylistMmobjs($series)
    {
        $mms = $series->getPlaylist()->getMultimediaObjects();
        $adapter = new DoctrineCollectionAdapter($mms);
        $pagerfanta = new Pagerfanta($adapter);
        return $pagerfanta;
    }

    /**
     * Create new resource
     */
    public function addMmobjAction(Series $series, Request $request)
    {
        if(!$request->query->has('mm_id'))
            throw new \Exception('The request is missing the \'mm_id\' parameter');

        $playlistEmbed = $series->getPlaylist();
        $mmobjId = $request->query->get('mm_id');
        $mm = $mmobjRepo->find($mmobjId);
        if(!$mm)
            throw new \Exception("The id: $mmobjId , does not belong to any Multimedia Object");

        $playlistEmbed->addMultimediaObject($mm);
        $dm->persist($series);
        $dm->flush();
    }

    /**
     * @Template("PumukitNewAdminBundle:PlaylistMultimediaObject:add_mmobj_modal.html.twig")
     */
    public function addMmobjModalAction()
    {
        /* $service = $this->get('pumukitschema.person');
           $mmobjs = $service->getMmobjsFromUser($this->getUser()); */
        //MOCK
        $mmobjs = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:MultimediaObject')->findAll();
        return array('my_mmobjs' => $mmobjs);
    }
    /**
     * @Template("PumukitNewAdminBundle:PlaylistMultimediaObject:search_mmobjs_modal.html.twig")
     */
    public function searchMmobjsModalAction(Request $request)
    {
        $value = $request->query->get('search', '');
        $criteria = array('$text' => array('$search' => $value));
        $queryBuilder = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:MultimediaObject')->createQueryBuilder();
        $queryBuilder->setQueryArray($criteria);
        $adapter = new DoctrineODMMongoDBAdapter($queryBuilder);
        $mmobjs = new Pagerfanta($adapter);
        return array('mmobjs' => $mmobjs);
    }

    /**
     * @Template("PumukitNewAdminBundle:PlaylistMultimediaObject:url_mmobj_modal.html.twig")
     */
    public function urlMmobjModalAction(Request $request)
    {
        $id = $request->query->get('mmid', '');
        $mmobj = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:MultimediaObject')->find($id);
        return array('mmobj' => $mmobj);
    }


    public function addBatchMmobjsAction(Request $request)
    {
        return $this->redirect($this->generateUrl('pumukitnewadmin_playlistmms_list'));
    }
}
