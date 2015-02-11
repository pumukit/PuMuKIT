<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\AdminBundle\Form\Type\TrackType;
use Pumukit\AdminBundle\Form\Type\TrackUpdateType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\EncoderBundle\Document\Job;

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

        $masterProfiles = $this->get('pumukitencoder.profile')->getMasterProfiles(true);

        $dirs = $this->get('pumukitschema.track')->getTempDirs();

        return array(
                     'track' => $track,
                     'form' => $form->createView(),
                     'mm' => $multimediaObject,
                     'master_profiles' => $masterProfiles,
                     'dirs' => $dirs
                     );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template
     */
    public function uploadAction(MultimediaObject $multimediaObject, Request $request)
    {
        $profile = $request->get('profile');
        $priority = $request->get('priority', 2);
        $formData = $request->get('pumukitadmin_track', array());
        list($language, $description) = $this->getArrayData($formData);

        $trackService = $this->get('pumukitschema.track');

        if (($request->files->has('resource')) && ("file" == $request->get('file_type'))) {
            $multimediaObject = $trackService->createTrackFromLocalHardDrive($multimediaObject, $request->files->get('resource'), $profile, $priority, $language, $description);
        } elseif (($request->get('file', null)) && ("inbox" == $request->get('file_type'))) {
            $multimediaObject = $trackService->createTrackFromInboxOnServer($multimediaObject, $request->get('file'), $profile, $priority, $language, $description);
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

        $profiles = $this->get('pumukitencoder.profile')->getProfiles();

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
                                   'track'    => $track,
                                   'form'     => $form->createView(),
                                   'mmId'     => $multimediaObject->getId(),
                                   'profiles' => $profiles
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
        $jobs = $this->get('pumukitencoder.job')->getJobsByMultimediaObjectId($multimediaObject->getId());
        $jobStatusError = $this->get('pumukitencoder.job')->getStatusError();

        $notMasterProfiles = $this->get('pumukitencoder.profile')->getMasterProfiles(false);

        return array(
                     'mmId' => $multimediaObject->getId(),
                     'tracks' => $multimediaObject->getTracks(),
                     'jobs' => $jobs,
                     'status_error'  => $jobStatusError,
                     'not_master_profiles' => $notMasterProfiles,
                     'oc' => ''
                     );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("job", class="PumukitEncoderBundle:Job", options={"id" = "jobId"})
     */
    public function retryJobAction(MultimediaObject $multimediaObject, Job $job, Request $request)
    {
        $flashMessage = $this->get('pumukitencoder.job')->retryJob($job);
        $this->addFlash('success', $flashMessage);

        return $this->redirect($this->generateUrl('pumukitadmin_track_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function deleteJobAction(MultimediaObject $multimediaObject, Request $request)
    {
        $this->get('pumukitencoder.job')->deleteJob($request->get('jobId'));

        $this->addFlash('success', 'delete job');

        return $this->redirect($this->generateUrl('pumukitadmin_track_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function autocompleteAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));

        $this->get('pumukit.inspection')->autocompleteTrack($track);
        $this->get('pumukitschema.track')->updateTrackInMultimediaObject($multimediaObject);

        return $this->redirect($this->generateUrl('pumukitadmin_track_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template("PumukitAdminBundle:Pic:list.html.twig")
     */
    public function picAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $numframe = $request->get('numframe');

        $flagTrue = $this->get('pumukitencoder.picextractor')->extractPic($multimediaObject, $track, $numframe);
        if ($flagTrue) {
            $this->get('pumukitschema.track')->updateTrackInMultimediaObject($multimediaObject);
        }

        return array(
                     'resource'      => $multimediaObject,
                     'resource_name' => 'mms'
                     );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function downloadAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));

        $response = new BinaryFileResponse($track->getPath());
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
                                         ResponseHeaderBag::DISPOSITION_INLINE,
                                         basename($track->getPath()),
                                         iconv('UTF-8', 'ASCII//TRANSLIT', basename($track->getPath()))
                                         );

        return $response;
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function retranscodeAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $profile = $request->get('profile');
        $priority = 2;

        $trackService = $this->get('pumukitschema.track');

        $this->get('pumukitencoder.job')->addJob($track->getPath(), $profile, $priority, $multimediaObject, $track->getLanguage(), $track->getI18nDescription());

        return $this->redirect($this->generateUrl('pumukitadmin_track_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * Get data in array or default values
     */
    private function getArrayData($formData)
    {
        $language = null;
        $description = array();

        if (array_key_exists('language', $formData)) {
            $language = $formData['language'];
        }
        if (array_key_exists('i18n_description', $formData)) {
            $description = $formData['i18n_description'];
        }

        return array($language, $description);
    }
}