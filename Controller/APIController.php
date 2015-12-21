<?php

namespace Pumukit\VideoEditorBundle\Controller;

use Pumukit\VideoEditorBundle\Document\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 *  @Route("/api/annotations")
 */
class APIController extends Controller
{
    /**
     * @Route("/annotations.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     */
    public function getAction(Request $request)
    {
        //TODO: Do the annotation getting using a service function.
        //$opencastAnnotationService = $this->container->get('video_editor.opencast_annotations');
        $serializer = $this->get('serializer');
        $limit = $request->get('limit')?:10;
        $offset = $request->get('offset')?:0;
        $total = 10;
        
        //TODO: Do the annotation getting using a service function.
        //$resAnnotations = $opencastAnnotationService->getOpencastAnnotations();
        $resAnnotations = array();
        $annonRepo = $this->get('doctrine_mongodb')->getRepository('PumukitVideoEditorBundle:Annotation');
        $resAnnotations = $annonRepo->findAll();

        $data = array('limit' => $limit,
                      'offset' => $offset,
                      'total' => $total,
                      'annotation' => $resAnnotations);
        
        $response = $serializer->serialize($data, $request->getRequestFormat());
        return new Response($response);
    }

    /**
     * @Route("/{id}.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     */
    public function getByIdAction(Request $request, $id)
    {
        //TODO: Do the annotation getting using a service function.
        //$opencastAnnotationService = $this->container->get('video_editor.opencast_annotations');
        $serializer = $this->get('serializer');

        //TODO: Do the annotation getting using a service function.
        //$resAnnotations = $opencastAnnotationService->getOpencastAnnotations();
        $resAnnotations = array();
        $annonRepo = $this->get('doctrine_mongodb')->getRepository('PumukitVideoEditorBundle:Annotation');
        $resAnnotations = $annonRepo->find($id);

        $data = array('annotation' => $resAnnotations);
        
        $response = $serializer->serialize($data, $request->getRequestFormat());
        return new Response($response);
    }

    /**
     * @Route("/")
     * @Method("PUT")
     */
    public function createNewAction(Request $request)
    {
        //TODO: Do the annotation getting using a service function.
        //$opencastAnnotationService = $this->container->get('video_editor.opencast_annotations');
        $serializer = $this->get('serializer');
        
        //$annonRepo = $this->get('doctrine_mongodb')->getRepository('PumukitVideoEditorBundle:Annotation');
        $episode = $request->get('episode');
        $type = $request->get('type');
        $value = $request->get('value');
        $inPoint = $request->get('in');
        $outPoint = $request->get('out')?:100;
        $isPrivate = $request->get('isPrivate')?:false;

        $annotation = new Annotation();
        $annotation->setMultimediaObject(new \MongoId($episode));
        $annotation->setType($type);
        $annotation->setValue($value);
        $annotation->setInPoint($inPoint);
        $annotation->setOutPoint($outPoint);
        $annotation->setIsPrivate($isPrivate);
        $annotation->setLength(0);//This field is not very useful.
        $annotation->setCreated(new \DateTime());
        $annotation->setUserId('anonymous');//TODO: How do we get the user_id? 
        $annotation->setSession('session');
        
        $this->get('doctrine_mongodb.odm.document_manager')->persist($annotation);
        $this->get('doctrine_mongodb.odm.document_manager')->flush();
        
        $data = array('annotation' => $annotation);
        $response = $serializer->serialize($data, 'xml');
        return new Response($response);
    }

    /**
     * @Route("/{id}")
     * @Method("PUT")
     */
    public function editAction(Annotation $annotation,Request $request)
    {
        $value = $request->get('value');        
        $annotation->setValue($value);
        $annonRepo = $this->get('doctrine_mongodb.odm.document_manager')->persist($annotation);
        $annonRepo = $this->get('doctrine_mongodb.odm.document_manager')->flush();
        
        $response = $serializer->serialize($annotation, 'xml');
        return new Response($response);
    }

    /**
     * @Route("/{id}")
     * @Method("DELETE")
     */
    public function deleteAction(Annotation $annotation,Request $request)
    {
        $this->get('doctrine_mongodb.odm.document_manager')->remove($annotation);
        $this->get('doctrine_mongodb.odm.document_manager')->flush();
        
        $response = $serializer->serialize($annotation, 'xml');
        return new Response($response);
    }

}
