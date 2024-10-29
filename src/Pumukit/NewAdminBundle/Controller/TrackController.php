<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\DTO\JobOptions;
use Pumukit\EncoderBundle\Services\JobCreator;
use Pumukit\EncoderBundle\Services\JobRemover;
use Pumukit\EncoderBundle\Services\JobRender;
use Pumukit\EncoderBundle\Services\JobUpdater;
use Pumukit\EncoderBundle\Services\PicExtractorService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\Repository\JobRepository;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\MediaRemover;
use Pumukit\SchemaBundle\Services\MediaUpdater;
use Pumukit\SchemaBundle\Services\TrackService;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class TrackController extends AbstractController implements NewAdminControllerInterface
{
    private LoggerInterface $logger;
    private DocumentManager $documentManager;
    private TrackService $trackService;
    private ProfileService $profileService;
    private InspectionFfprobeService $inspectionService;
    private PicExtractorService $picExtractorService;
    private $kernelEnvironment;
    private $kernelBundles;
    private JobCreator $jobCreator;
    private JobRender $jobRender;
    private JobRepository $jobRepository;
    private JobUpdater $jobUpdater;
    private JobRemover $jobRemover;
    private MediaRemover $mediaRemover;
    private MediaUpdater $mediaUpdater;
    private i18nService $i18nService;

    public function __construct(
        LoggerInterface $logger,
        DocumentManager $documentManager,
        JobCreator $jobCreator,
        JobUpdater $jobUpdater,
        JobRemover $jobRemover,
        JobRender $jobRender,
        JobRepository $jobRepository,
        MediaUpdater $mediaUpdater,
        MediaRemover $mediaRemover,
        i18nService $i18nService,
        TrackService $trackService,
        ProfileService $profileService,
        InspectionFfprobeService $inspectionService,
        PicExtractorService $picExtractorService,
        $kernelEnvironment,
        $kernelBundles
    ) {
        $this->logger = $logger;
        $this->documentManager = $documentManager;
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
        $this->mediaRemover = $mediaRemover;
        $this->mediaUpdater = $mediaUpdater;
        $this->i18nService = $i18nService;
    }

    /**
     * @Security("is_granted('ROLE_ACCESS_ADVANCED_UPLOAD')")
     */
    public function createAction(MultimediaObject $multimediaObject): Response
    {
        $masterProfiles = $this->profileService->getMasterProfiles(true);

        return $this->render('@PumukitNewAdmin/Media/create.html.twig', [
            'mm' => $multimediaObject,
            'series' => $multimediaObject->getSeries(),
            'master_profiles' => $masterProfiles,
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ACCESS_ADVANCED_UPLOAD')")
     */
    public function uploadAction(Request $request, MultimediaObject $multimediaObject): JsonResponse
    {
        $profile = $request->get('profile_option');
        $priority = (int) $request->get('priority', 2);
        $formData = $request->get('pumukitnewadmin_track', []);
        [$language, $description] = $this->getArrayData($formData);

        try {
            if (0 === $request->files->count() && 0 === $request->request->count()) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }

            if ($request->files->has('resource')) {
                // Uppy XHR Upload
                $files = $request->files->get('resource');
                $file = reset($files);
                $jobOptions = new JobOptions($profile, $priority, $language, $description);
                $this->jobCreator->fromUploadedFile($multimediaObject, $file, $jobOptions);
            } elseif ($request->get('file')) {
                // Inbox server Upload
                $jobOptions = new JobOptions($profile, $priority, $language, $description);
                $path = Path::create($request->get('file'));
                $this->jobCreator->fromPath($multimediaObject, $path, $jobOptions);
            } else {
                throw new \Exception('Not received file or file type is not valid.');
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
    public function toggleHideAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        try {
            $track = $multimediaObject->getTrackById($request->get('id'));
            $track->changeHide();
            $this->documentManager->flush();
        } catch (\Exception $e) {
            return new Response('Cannot change visibility on this track.', 400);
        }

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['reload_links' => true, 'id' => $multimediaObject->getId()]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function updateAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $isPlayable = $track->tags()->containsTag('display');
        $isPublished = $multimediaObject->containsTagWithCod(PumukitWebTVBundle::WEB_TV_TAG) && MultimediaObject::STATUS_PUBLISHED == $multimediaObject->getStatus();
        $job = null;
        if ($track->storage()->path()->path()) {
            $job = $this->documentManager->getRepository(Job::class)->findOneBy(['path_end' => $track->storage()->path()->path()]);
        }

        $profiles = $this->profileService->getProfiles();

        if ($request->isMethod('POST')) {
            try {
                if ($request->get('hide')) {
                    $this->mediaUpdater->updateHide($multimediaObject, $track, true);
                }

                if ($request->get('download')) {
                    $this->mediaUpdater->updateDownload($multimediaObject, $track, true);
                }

                $this->mediaUpdater->updateLanguage($multimediaObject, $track, $request->get('language'));
                $tags = Tags::create(explode(',', $request->get('tags')));
                $this->mediaUpdater->updateTags($multimediaObject, $track, $tags);

                $i18nDescription = i18nText::create($this->i18nService->generateI18nText($request->get('i18n_description')));
                $this->mediaUpdater->updateDescription($multimediaObject, $track, $i18nDescription);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), 400);
            }

            return $this->redirectToRoute('pumukitnewadmin_track_list', ['reload_links' => true, 'id' => $multimediaObject->getId()]);
        }

        return $this->render(
            '@PumukitNewAdmin/Media/update.html.twig',
            [
                'track' => $track,
                'mm' => $multimediaObject,
                'profiles' => $profiles,
                'is_playable' => $isPlayable,
                'is_published' => $isPublished,
                'job' => $job,
            ]
        );
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function playAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        $track = $multimediaObject->getTrackById($request->get('id'));

        return $this->render('@PumukitNewAdmin/Media/play.html.twig', ['track' => $track]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function deleteAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        $media = $multimediaObject->getMediaById($request->get('id'));
        if ($media) {
            if ($media->tags()->contains('opencast') && $multimediaObject->isMultistream()) {
                return new Response('You can\'t delete this track. It is an Opencast track and the multimedia object is multistream.', Response::HTTP_FORBIDDEN);
            }
            if ($media->isMaster() && !$this->isGranted(Permission::ACCESS_ADVANCED_UPLOAD)) {
                return new Response('You don\'t have enough permissions to delete this track. Contact your administrator.', Response::HTTP_FORBIDDEN);
            }

            $this->mediaRemover->remove($multimediaObject, $media);
        }

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function upAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        $multimediaObject = $this->trackService->upTrackInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'up');

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function downAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        $multimediaObject = $this->trackService->downTrackInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'down');

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    public function listAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        $jobs = $this->jobRepository->getNotFinishedJobsByMultimediaObjectId($multimediaObject->getId());

        $notMasterProfiles = $this->profileService->getProfiles(null, true, false);
        $opencastExists = array_key_exists('PumukitOpencastBundle', $this->kernelBundles);

        return $this->render('@PumukitNewAdmin/Media/list.html.twig', [
            'mm' => $multimediaObject,
            'tracks' => $multimediaObject->getMedias(),
            'jobs' => $jobs,
            'not_master_profiles' => $notMasterProfiles,
            'oc' => '',
            'opencast_exists' => $opencastExists,
            'reload_links' => $request->query->get('reload_links', false),
        ]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("job", options={"id" = "jobId"})
     */
    public function retryJobAction(MultimediaObject $multimediaObject, Job $job): Response
    {
        $flashMessage = $this->jobUpdater->retryJob($job);
        $this->addFlash('success', $flashMessage);

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("job", options={"id" = "jobId"})
     */
    public function infoJobAction(MultimediaObject $multimediaObject, Job $job): Response
    {
        $command = $this->jobRender->renderBat($job);

        return $this->render('@PumukitNewAdmin/Media/info.html.twig', [
            'multimediaObject' => $multimediaObject,
            'job' => $job,
            'command' => $command,
        ]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function deleteJobAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        $job = $this->jobRepository->searchJob($request->get('jobId'));
        $this->jobRemover->delete($job);

        $this->addFlash('success', 'delete job');

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    public function updateJobPriorityAction(Request $request): JsonResponse
    {
        $priority = (int) $request->get('priority');
        $job = $this->jobRepository->searchJob($request->get('jobId'));
        $this->jobUpdater->updateJobPriority($job, $priority);

        return new JsonResponse(['jobId' => $job->getId(), 'priority' => $priority]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function picAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $numFrame = $request->get('numframe');

        $flagTrue = $this->picExtractorService->extractPic($multimediaObject, $track, $numFrame);
        if ($flagTrue) {
            $this->trackService->updateTrackInMultimediaObject($multimediaObject, $track);
        }

        return $this->render('@PumukitNewAdmin/Pic/list.html.twig', [
            'resource' => $multimediaObject,
            'resource_name' => 'mms',
        ]);
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function downloadAction(Request $request, MultimediaObject $multimediaObject): BinaryFileResponse
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $path = $track->storage()->path()->path();

        $response = new BinaryFileResponse($path);
        $response::trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($path),
            iconv('UTF-8', 'ASCII//TRANSLIT', basename($path))
        );

        return $response;
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     */
    public function retranscodeAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        $track = $multimediaObject->getTrackById($request->get('id'));
        $profile = $request->get('profile');
        $priority = 2;

        $jobOptions = new JobOptions($profile, $priority, $track->language(), $track->description()->toArray(), []);
        $path = Path::create($track->storage()->path()->path());
        $this->jobCreator->fromPath($multimediaObject, $path, $jobOptions);

        return $this->redirectToRoute('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]);
    }

    private function getArrayData($formData): array
    {
        $language = 'en';
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
