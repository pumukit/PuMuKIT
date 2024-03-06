<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\DTO\JobOptions;
use Pumukit\EncoderBundle\Services\JobCreator;
use Pumukit\EncoderBundle\Services\JobRemover;
use Pumukit\EncoderBundle\Services\JobRender;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\JobUpdater;
use Pumukit\EncoderBundle\Services\PicExtractorService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\Repository\JobRepository;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\NewAdminBundle\Form\Type\TrackType;
use Pumukit\NewAdminBundle\Form\Type\TrackUpdateType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class TrackController extends AbstractController implements NewAdminControllerInterface
{
    private $logger;
    private $documentManager;
    private $translator;
    private $trackService;
    private $profileService;
    private $inspectionService;
    private $picExtractorService;
    private $kernelEnvironment;
    private $kernelBundles;
    private JobCreator $jobCreator;
    private JobRender $jobRender;
    private JobRepository $jobRepository;
    private JobUpdater $jobUpdater;
    private JobRemover $jobRemover;

    public function __construct(
        LoggerInterface $logger,
        DocumentManager $documentManager,
        TranslatorInterface $translator,
        JobCreator $jobCreator,
        JobUpdater $jobUpdater,
        JobRemover $jobRemover,
        JobRender $jobRender,
        JobRepository $jobRepository,
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
        $this->trackService = $trackService;
        $this->profileService = $profileService;
        $this->inspectionService = $inspectionService;
        $this->picExtractorService = $picExtractorService;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->kernelBundles = $kernelBundles;
        $this->jobCreator = $jobCreator;
        $this->jobRender = $jobRender;
        $this->jobRepository = $jobRepository;
        $this->jobUpdater = $jobUpdater;
        $this->jobRemover = $jobRemover;
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
     * @Security("is_granted('ROLE_ACCESS_ADVANCED_UPLOAD')")
     */
    public function uploadAction(Request $request, MultimediaObject $multimediaObject)
    {
        $profile = $request->get('profile');
        $priority = (int) $request->get('priority', 2);
        $formData = $request->get('pumukitnewadmin_track', []);
        [$language, $description] = $this->getArrayData($formData);

        try {
            if (0 === $request->files->count() && 0 === $request->request->count()) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if ($request->files->has('resource') && ('file' === $request->get('file_type'))) {
                $files = $request->files->get('resource');
                $file = reset($files);
                $jobOptions = new JobOptions($profile, $priority, $language, $description);
                $multimediaObject = $this->jobCreator->fromUploadedFile($multimediaObject, $file, $jobOptions);
            } elseif ($request->get('file') && ('inbox' === $request->get('file_type'))) {
                $jobOptions = new JobOptions($profile, $priority, $language, $description);
                $path = Path::create($request->get('file'));
                $this->jobCreator->fromPath($multimediaObject, $path, $jobOptions);
            }
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());

            $message = ('dev' === $this->kernelEnvironment) ? $e->getMessage() : 'The file is not a valid video or audio file';

            return new JsonResponse([
                'mm' => $multimediaObject,
                'uploaded' => 'failed',
                'message' => $message,
            ]);
        }

        return new JsonResponse([
            'mm' => $multimediaObject,
            'uploaded' => 'success',
            'message' => 'New Track added.',
            'endPage' => $this->generateUrl('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
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
     */
    public function infoAction(Request $request, MultimediaObject $multimediaObject)
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $isPlayable = $track->tags()->containsTag('display');
        $isPublished = $multimediaObject->containsTagWithCod(PumukitWebTVBundle::WEB_TV_TAG) && MultimediaObject::STATUS_PUBLISHED == $multimediaObject->getStatus();

        $job = null;
        if ($track->storage()->path()->path()) {
            $job = $this->documentManager->getRepository(Job::class)->findOneBy(['path_end' => $track->storage()->path()->path()]);
        }

        return $this->render("@PumukitNewAdmin/Track/info.html.twig", [
            'track' => $track,
            'job' => $job,
            'mm' => $multimediaObject,
            'is_playable' => $isPlayable,
            'is_published' => $isPublished,
        ]);
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
        $jobs = $this->jobRepository->getNotFinishedJobsByMultimediaObjectId($multimediaObject->getId());

        $notMasterProfiles = $this->profileService->getProfiles(null, true, false);
        $opencastExists = array_key_exists('PumukitOpencastBundle', $this->kernelBundles);

        return [
            'mm' => $multimediaObject,
            'tracks' => $multimediaObject->getMedias(),
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
        $flashMessage = $this->jobUpdater->retryJob($job);
        $this->addFlash('success', $flashMessage);

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("job", options={"id" = "jobId"})
     *
     * @Template("@PumukitNewAdmin/Track/infoJob.html.twig")
     */
    public function infoJobAction(MultimediaObject $multimediaObject, Job $job)
    {
        $command = $this->jobRender->renderBat($job);

        return ['multimediaObject' => $multimediaObject, 'job' => $job, 'command' => $command];
    }

    /**
     * See: Pumukit\EncoderBundle\Controller\InfoController::deleteJobAction.
     *
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function deleteJobAction(Request $request, MultimediaObject $multimediaObject)
    {
        $job = $this->jobRepository->searchJob($request->get('jobId'));
        $this->jobRemover->delete($job);

        $this->addFlash('success', 'delete job');

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    /**
     * See: Pumukit\EncoderBundle\Controller\InfoController::updateJobPriorityAction.
     */
    public function updateJobPriorityAction(Request $request)
    {
        $priority = (int) $request->get('priority');
        $job = $this->jobRepository->searchJob($request->get('jobId'));
        $this->jobUpdater->updateJobPriority($job, $priority);

        return new JsonResponse(['jobId' => $job->getId(), 'priority' => $priority]);
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

        $jobOptions = new JobOptions($profile, $priority, $track->language(), $track->description(), []);
        $path = Path::create($track->storage()->path()->path());
        $this->jobCreator->fromPath($multimediaObject, $path, $jobOptions);

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
