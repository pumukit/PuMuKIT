<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\PicExtractorService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\NewAdminBundle\Form\Type\TrackType;
use Pumukit\NewAdminBundle\Form\Type\TrackUpdateType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\TrackService;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class TrackController extends AbstractController implements NewAdminControllerInterface
{
    private $logger;
    private $documentManager;
    private $translator;
    private $jobService;
    private $trackService;
    private $profileService;
    private $inspectionService;
    private $picExtractorService;
    private $kernelEnvironment;
    private $kernelBundles;

    public function __construct(
        LoggerInterface $logger,
        DocumentManager $documentManager,
        TranslatorInterface $translator,
        JobService $jobService,
        TrackService $trackService,
        ProfileService $profileService,
        InspectionFfprobeService $inspectionService,
        PicExtractorService $picExtractorService,
        $kernelEnvironment,
        $kernelBundles
    ) {
        $this->logger = $logger;
        $this->documentManager = $documentManager;
        $this->translator = $translator;
        $this->jobService = $jobService;
        $this->trackService = $trackService;
        $this->profileService = $profileService;
        $this->inspectionService = $inspectionService;
        $this->picExtractorService = $picExtractorService;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->kernelBundles = $kernelBundles;
    }

    /**
     * @Security("is_granted('ROLE_ACCESS_ADVANCED_UPLOAD')")
     *
     * @Template("@PumukitNewAdmin/Track/create.html.twig")
     */
    public function createAction(Request $request, MultimediaObject $multimediaObject)
    {
        $locale = $request->getLocale();
        $track = new Track();
        $form = $this->createForm(TrackType::class, $track, ['translator' => $this->translator, 'locale' => $locale]);

        $masterProfiles = $this->profileService->getMasterProfiles(true);

        return [
            'track' => $track,
            'form' => $form->createView(),
            'mm' => $multimediaObject,
            'master_profiles' => $masterProfiles,
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Track/upload.html.twig")
     *
     * @Security("is_granted('ROLE_ACCESS_ADVANCED_UPLOAD')")
     */
    public function uploadAction(Request $request, MultimediaObject $multimediaObject)
    {
        $profile = $request->get('profile');
        $priority = $request->get('priority', 2);
        $formData = $request->get('pumukitnewadmin_track', []);
        [$language, $description] = $this->getArrayData($formData);

        try {
            if (0 === $request->files->count() && 0 === $request->request->count()) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if ($request->files->has('resource') && ('file' === $request->get('file_type'))) {
                $file = $request->files->get('resource');
                $multimediaObject = $this->jobService->createTrackFromLocalHardDrive($multimediaObject, reset($file), $profile, $priority, $language, $description);
            } elseif ($request->get('file') && ('inbox' === $request->get('file_type'))) {
                $multimediaObject = $this->jobService->createTrackFromInboxOnServer($multimediaObject, $request->get('file'), $profile, $priority, $language, $description);
            }
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());

            $message = ('dev' === $this->kernelEnvironment) ? $e->getMessage() : 'The file is not a valid video or audio file';

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
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function toggleHideAction(Request $request, MultimediaObject $multimediaObject)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $track->setHide(!$track->getHide());

        try {
            $multimediaObject = $this->trackService->updateTrackInMultimediaObject($multimediaObject, $track);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 400);
        }

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['reload_links' => true, 'id' => $multimediaObject->getId()]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function updateAction(Request $request, MultimediaObject $multimediaObject)
    {
        $locale = $request->getLocale();
        $track = $multimediaObject->getTrackById($request->get('id'));
        $form = $this->createForm(TrackUpdateType::class, $track, ['translator' => $this->translator, 'locale' => $locale, 'is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN')]);

        $profiles = $this->profileService->getProfiles();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($request->isMethod('PUT') || $request->isMethod('POST'))) {
            try {
                $multimediaObject = $this->trackService->updateTrackInMultimediaObject($multimediaObject, $track);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), 400);
            }

            return $this->redirectToRoute('pumukitnewadmin_track_list', ['reload_links' => true, 'id' => $multimediaObject->getId()]);
        }

        return $this->render(
            '@PumukitNewAdmin/Track/update.html.twig',
            [
                'track' => $track,
                'form' => $form->createView(),
                'mmId' => $multimediaObject->getId(),
                'profiles' => $profiles,
            ]
        );
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     *
     * @Template("@PumukitNewAdmin/Track/info.html.twig")
     */
    public function infoAction(Request $request, MultimediaObject $multimediaObject)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $isPlayable = $track->containsTag('display');
        $isPublished = $multimediaObject->containsTagWithCod(PumukitWebTVBundle::WEB_TV_TAG) && MultimediaObject::STATUS_PUBLISHED == $multimediaObject->getStatus();

        $job = null;
        if ($track->getPath()) {
            $job = $this->documentManager->getRepository(Job::class)->findOneBy(['path_end' => $track->getPath()]);
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
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     *
     * @Template("@PumukitNewAdmin/Track/play.html.twig")
     */
    public function playAction(Request $request, MultimediaObject $multimediaObject)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));

        return ['track' => $track];
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function deleteAction(Request $request, MultimediaObject $multimediaObject)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        if ($track) {
            if (($track->containsTag('opencast') && $multimediaObject->isMultistream())
                || ($track->isMaster() && !$this->isGranted(Permission::ACCESS_ADVANCED_UPLOAD))) {
                return new Response('You don\'t have enough permissions to delete this track. Contact your administrator.', Response::HTTP_FORBIDDEN);
            }
            $multimediaObject = $this->trackService->removeTrackFromMultimediaObject($multimediaObject, $request->get('id'));
        }

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function upAction(Request $request, MultimediaObject $multimediaObject)
    {
        $multimediaObject = $this->trackService->upTrackInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'up');

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function downAction(Request $request, MultimediaObject $multimediaObject)
    {
        $multimediaObject = $this->trackService->downTrackInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'down');

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    /**
     * @Template("@PumukitNewAdmin/Track/list.html.twig")
     */
    public function listAction(Request $request, MultimediaObject $multimediaObject)
    {
        $jobs = $this->jobService->getNotFinishedJobsByMultimediaObjectId($multimediaObject->getId());

        $notMasterProfiles = $this->profileService->getProfiles(null, true, false);
        $opencastExists = array_key_exists('PumukitOpencastBundle', $this->kernelBundles);

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
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("job", options={"id" = "jobId"})
     */
    public function retryJobAction(MultimediaObject $multimediaObject, Job $job)
    {
        $flashMessage = $this->jobService->retryJob($job);
        $this->addFlash('success', $flashMessage);

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    /**
     * See: Pumukit\EncoderBundle\Controller\InfoController::infoJobAction.
     *
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("job", options={"id" = "jobId"})
     *
     * @Template("@PumukitNewAdmin/Track/infoJob.html.twig")
     */
    public function infoJobAction(MultimediaObject $multimediaObject, Job $job)
    {
        $command = $this->jobService->renderBat($job);

        return ['multimediaObject' => $multimediaObject, 'job' => $job, 'command' => $command];
    }

    /**
     * See: Pumukit\EncoderBundle\Controller\InfoController::deleteJobAction.
     *
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function deleteJobAction(Request $request, MultimediaObject $multimediaObject)
    {
        $this->jobService->deleteJob($request->get('jobId'));

        $this->addFlash('success', 'delete job');

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    /**
     * See: Pumukit\EncoderBundle\Controller\InfoController::updateJobPriorityAction.
     */
    public function updateJobPriorityAction(Request $request)
    {
        $priority = $request->get('priority');
        $jobId = $request->get('jobId');
        $this->jobService->updateJobPriority($jobId, $priority);

        return new JsonResponse(['jobId' => $jobId, 'priority' => $priority]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function autocompleteAction(Request $request, MultimediaObject $multimediaObject)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));

        $this->inspectionService->autocompleteTrack($track);
        $this->trackService->updateTrackInMultimediaObject($multimediaObject, $track);

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     *
     * @Template("@PumukitNewAdmin/Pic/list.html.twig")
     */
    public function picAction(Request $request, MultimediaObject $multimediaObject)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $numframe = $request->get('numframe');

        $flagTrue = $this->picExtractorService->extractPic($multimediaObject, $track, $numframe);
        if ($flagTrue) {
            $this->trackService->updateTrackInMultimediaObject($multimediaObject, $track);
        }

        return [
            'resource' => $multimediaObject,
            'resource_name' => 'mms',
        ];
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function downloadAction(Request $request, MultimediaObject $multimediaObject)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));

        $response = new BinaryFileResponse($track->getPath());
        $response::trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($track->getPath()),
            iconv('UTF-8', 'ASCII//TRANSLIT', basename($track->getPath()))
        );

        return $response;
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function retranscodeAction(Request $request, MultimediaObject $multimediaObject)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $profile = $request->get('profile');
        $priority = 2;

        $this->jobService->addJob($track->getPath(), $profile, $priority, $multimediaObject, $track->getLanguage(), $track->getI18nDescription());

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

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
