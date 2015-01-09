<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\AdminBundle\Form\Type\TrackType;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class TrackController extends Controller
{
    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template
     */
    public function createAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = new Track();
        $form = $this->createForm(new TrackType(), $track);

        return array(
                     'track' => $track,
                     'form' => $form->createView(),
                     'mm' => $multimediaObject
                     );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template
     */
    public function uploadAction(MultimediaObject $multimediaObject, Request $request)
    {
        $formData = $request->get('pumukitadmin_track', array());

        // TODO
        $trackService = $this->get('pumukitschema.track');
        if (($request->files->has('file')) && (!$request->get('url', null))) {
            $multimediaObject = $trackService->addTrackToMultimediaObject($multimediaObject, $request->files->get('file'), $formData);
        } elseif ($request->get('url', null)) {
          // TODO - addTrackUrl not defined
          $multimediaObject = $trackService->addTrackUrl($multimediaObject, $request->get('url'), $formData);
        }

        return array('mm' => $multimediaObject);
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     *
     */
    public function updateAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $form = $this->createForm(new TrackUpdateType(), $track);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $multimediaObject = $this->get('pumukitadmin.track')->updateTrackInMultimediaObject($multimediaObject);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            return $this->redirect($this->generateUrl('pumukitadmin_track_list', array('id' => $multimediaObject->getId())));
        }

        return $this->render('PumukitAdminBundle:Track:update.html.twig',
                             array(
                                   'track' => $track,
                                   'form' => $form->createView(),
                                   'mmId' => $multimediaObject->getId()
                                   ));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template
     */
    public function infoAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        
        return array(
                     'track' => $track,
                     'mm' => $multimediaObject
                     );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function deleteAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.track')->removeTrackFromMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'delete');

        return $this->redirect($this->generateUrl('pumukitadmin_track_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function upAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.track')->upTrackInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'up');

        return $this->redirect($this->generateUrl('pumukitadmin_track_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function downAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.track')->downTrackInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'down');

        return $this->redirect($this->generateUrl('pumukitadmin_track_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @Template
     */
    public function listAction(MultimediaObject $multimediaObject)
    {
        return array(
                     'mmId' => $multimediaObject->getId(),
                     'tracks' => $multimediaObject->getTracks(),
                     'transcodings' => '',
                     'oc' => ''
                     );
    }
}