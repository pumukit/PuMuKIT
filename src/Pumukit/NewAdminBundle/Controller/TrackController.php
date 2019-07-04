<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\NewAdminBundle\Form\Type\TrackType;
use Pumukit\NewAdminBundle\Form\Type\TrackUpdateType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class TrackController extends Controller implements NewAdminControllerInterface
{
    /**
     * @Security("is_granted('ROLE_ACCESS_ADVANCED_UPLOAD')")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template
     */
    public function createAction(MultimediaObject $multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $track = new Track();
        $form = $this->createForm(TrackType::class, $track, ['translator' => $translator, 'locale' => $locale]);

        $masterProfiles = $this->get('pumukitencoder.profile')->getMasterProfiles(true);

        return [
            'track' => $track,
            'form' => $form->createView(),
            'mm' => $multimediaObject,
            'master_profiles' => $masterProfiles,
        ];
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template
     * @Security("is_granted('ROLE_ACCESS_ADVANCED_UPLOAD')")
     */
    public function uploadAction(MultimediaObject $multimediaObject, Request $request)
    {
        $profile = $request->get('profile');
        $priority = $request->get('priority', 2);
        $formData = $request->get('pumukitnewadmin_track', []);
        [$language, $description] = $this->getArrayData($formData);

        $jobService = $this->get('pumukitencoder.job');

        try {
            if (0 === $request->files->count() && 0 === $request->request->count()) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if (($request->files->has('resource')) && ('file' == $request->get('file_type'))) {
                $multimediaObject = $jobService->createTrackFromLocalHardDrive($multimediaObject, $request->files->get('resource'), $profile, $priority, $language, $description);
            } elseif (($request->get('file', null)) && ('inbox' == $request->get('file_type'))) {
                $multimediaObject = $jobService->createTrackFromInboxOnServer($multimediaObject, $request->get('file'), $profile, $priority, $language, $description);
            }
        } catch (\Exception $e) {
            $logger = $this->container->get('logger');
            $logger->warning($e->getMessage());

            $message = ('dev' === $this->getParameter('kernel.environment')) ? $e->getMessage() : 'The file is not a valid video or audio file';

            return [
                'mm' => $multimediaObject,
                'uploaded' => 'failed',
                'message' => $message,
            ];
        }

        return [
            'mm' => $multimediaObject,
            'uploaded' => 'success',
            'message' => 'New Track added.',
        ];
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function updateAction(MultimediaObject $multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $track = $multimediaObject->getTrackById($request->get('id'));
        $form = $this->createForm(TrackUpdateType::class, $track, ['translator' => $translator, 'locale' => $locale, 'is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN')]);

        $profiles = $this->get('pumukitencoder.profile')->getProfiles();

        $form->handleRequest($request);
        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->isValid()) {
            try {
                $multimediaObject = $this->get('pumukitschema.track')->updateTrackInMultimediaObject($multimediaObject, $track);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), 400);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['reload_links' => true, 'id' => $multimediaObject->getId()]));
        }

        return $this->render(
            'PumukitNewAdminBundle:Track:update.html.twig',
            [
                'track' => $track,
                'form' => $form->createView(),
                'mmId' => $multimediaObject->getId(),
                'profiles' => $profiles,
            ]
        );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template
     */
    public function infoAction(MultimediaObject $multimediaObject, Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $track = $multimediaObject->getTrackById($request->get('id'));
        $isPlayable = $track->containsTag('display');
        $isPublished = $multimediaObject->containsTagWithCod('PUCHWEBTV') && MultimediaObject::STATUS_PUBLISHED == $multimediaObject->getStatus();

        $job = null;
        if ($track->getPath()) {
            $job = $dm->getRepository(Job::class)->findOneBy(['path_end' => $track->getPath()]);
        }

        return [
            'track' => $track,
            'job' => $job,
            'mm' => $multimediaObject,
            'is_playable' => $isPlayable,
            'is_published' => $isPublished,
        ];
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template
     */
    public function playAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));

        return ['track' => $track];
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function deleteAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        if ($track) {
            if (($track->containsTag('opencast') && $multimediaObject->getProperty('opencast')) ||
                ($track->isMaster() && !$this->isGranted(Permission::ACCESS_ADVANCED_UPLOAD))) {
                return new Response('You don\'t have enough permissions to delete this track. Contact your administrator.', Response::HTTP_FORBIDDEN);
            }
            $multimediaObject = $this->get('pumukitschema.track')->removeTrackFromMultimediaObject($multimediaObject, $request->get('id'));
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function upAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.track')->upTrackInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'up');

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function downAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.track')->downTrackInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'down');

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * @Template
     */
    public function listAction(MultimediaObject $multimediaObject, Request $request)
    {
        $jobs = $this->get('pumukitencoder.job')->getNotFinishedJobsByMultimediaObjectId($multimediaObject->getId());

        $notMasterProfiles = $this->get('pumukitencoder.profile')->getProfiles(null, true, false);
        $allBundles = $this->container->getParameter('kernel.bundles');
        $opencastExists = array_key_exists('PumukitOpencastBundle', $allBundles);

        return [
            'mm' => $multimediaObject,
            'tracks' => $multimediaObject->getTracks(),
            'jobs' => $jobs,
            'not_master_profiles' => $notMasterProfiles,
            'oc' => '',
            'opencast_exists' => $opencastExists,
            'reload_links' => $request->query->get('reload_links', false),
        ];
    }

    /**
     * See: Pumukit\EncoderBundle\Controller\InfoController::retryJobAction.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("job", class="PumukitEncoderBundle:Job", options={"id" = "jobId"})
     */
    public function retryJobAction(MultimediaObject $multimediaObject, Job $job, Request $request)
    {
        $flashMessage = $this->get('pumukitencoder.job')->retryJob($job);
        $this->addFlash('success', $flashMessage);

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * See: Pumukit\EncoderBundle\Controller\InfoController::infoJobAction.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("job", class="PumukitEncoderBundle:Job", options={"id" = "jobId"})
     * @Template
     */
    public function infoJobAction(MultimediaObject $multimediaObject, Job $job, Request $request)
    {
        $command = $this->get('pumukitencoder.job')->renderBat($job);

        return ['multimediaObject' => $multimediaObject, 'job' => $job, 'command' => $command];
    }

    /**
     * See: Pumukit\EncoderBundle\Controller\InfoController::deleteJobAction.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function deleteJobAction(MultimediaObject $multimediaObject, Request $request)
    {
        $this->get('pumukitencoder.job')->deleteJob($request->get('jobId'));

        $this->addFlash('success', 'delete job');

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * See: Pumukit\EncoderBundle\Controller\InfoController::updateJobPriorityAction.
     */
    public function updateJobPriorityAction(Request $request)
    {
        $priority = $request->get('priority');
        $jobId = $request->get('jobId');
        $this->get('pumukitencoder.job')->updateJobPriority($jobId, $priority);

        return new JsonResponse(['jobId' => $jobId, 'priority' => $priority]);
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function autocompleteAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));

        $this->get('pumukit.inspection')->autocompleteTrack($track);
        $this->get('pumukitschema.track')->updateTrackInMultimediaObject($multimediaObject, $track);

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template("PumukitNewAdminBundle:Pic:list.html.twig")
     */
    public function picAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $numframe = $request->get('numframe');

        $flagTrue = $this->get('pumukitencoder.picextractor')->extractPic($multimediaObject, $track, $numframe);
        if ($flagTrue) {
            $this->get('pumukitschema.track')->updateTrackInMultimediaObject($multimediaObject, $track);
        }

        return [
            'resource' => $multimediaObject,
            'resource_name' => 'mms',
        ];
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
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
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

        $this->get('pumukitencoder.job')->addJob($track->getPath(), $profile, $priority, $multimediaObject, $track->getLanguage(), $track->getI18nDescription());

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * Get data in array or default values.
     *
     * @param mixed $formData
     */
    private function getArrayData($formData)
    {
        $language = null;
        $description = [];

        if (array_key_exists('language', $formData)) {
            $language = $formData['language'];
        }
        if (array_key_exists('i18n_description', $formData)) {
            $description = $formData['i18n_description'];
        }

        return [$language, $description];
    }
}
