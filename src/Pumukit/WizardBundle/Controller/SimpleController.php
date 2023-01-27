<?php

declare(strict_types=1);

namespace Pumukit\WizardBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\SeriesService;
use Pumukit\SchemaBundle\Services\SortedMultimediaObjectsService;
use Pumukit\WizardBundle\Services\FormEventDispatcherService;
use Pumukit\WizardBundle\Services\LicenseService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_WIZARD_UPLOAD')")
 * @Route("/admin/simplewizard")
 */
class SimpleController extends AbstractController
{
    private $objectManager;
    private $licenseService;
    private $translator;
    private $inspectionFfprobeService;
    private $pumukitSchemaSortedMultimediaObjectService;
    private $jobService;
    private $profileService;
    private $factoryService;
    private $formEventDispatcherService;
    private $seriesServices;
    private $locales;
    private $pumukitWizardSimpleDefaultMasterProfile;
    private $pumukitWizardShowSimpleMmTitle;
    private $pumukitWizardShowSimpleSeriesTitle;
    private $pumukitCustomLanguages;

    public function __construct(
        DocumentManager $objectManager,
        LicenseService $licenseService,
        TranslatorInterface $translator,
        InspectionFfprobeService $inspectionFfprobeService,
        SortedMultimediaObjectsService $pumukitSchemaSortedMultimediaObjectService,
        JobService $jobService,
        ProfileService $profileService,
        FactoryService $factoryService,
        FormEventDispatcherService $formEventDispatcherService,
        SeriesService $seriesService,
        array $locales,
        bool $pumukitWizardShowSimpleMmTitle,
        bool $pumukitWizardShowSimpleSeriesTitle,
        array $pumukitCustomLanguages,
        $pumukitWizardSimpleDefaultMasterProfile
    ) {
        $this->objectManager = $objectManager;
        $this->licenseService = $licenseService;
        $this->translator = $translator;
        $this->inspectionFfprobeService = $inspectionFfprobeService;
        $this->profileService = $profileService;
        $this->pumukitSchemaSortedMultimediaObjectService = $pumukitSchemaSortedMultimediaObjectService;
        $this->jobService = $jobService;
        $this->profileService = $profileService;
        $this->factoryService = $factoryService;
        $this->formEventDispatcherService = $formEventDispatcherService;
        $this->seriesServices = $seriesService;
        $this->locales = $locales;
        $this->pumukitWizardSimpleDefaultMasterProfile = $pumukitWizardSimpleDefaultMasterProfile;
        $this->pumukitWizardShowSimpleMmTitle = $pumukitWizardShowSimpleMmTitle;
        $this->pumukitWizardShowSimpleSeriesTitle = $pumukitWizardShowSimpleSeriesTitle;
        $this->pumukitCustomLanguages = $pumukitCustomLanguages;
    }

    public function indexAction(Request $request, Series $series): Response
    {
        $licenseContent = $this->licenseService->getLicenseContent($request->getLocale());

        $languages = CustomLanguageType::getLanguageNames($this->pumukitCustomLanguages, $this->translator);

        return $this->render(
            '@PumukitWizard/Simple/index.html.twig',
            [
                'series' => $series,
                'languages' => $languages,
                'show_license' => $this->licenseService->isEnabled(),
                'license_text' => $licenseContent,
            ]
        );
    }

    /**
     * @Route("/upload/{id}", methods={"POST"}, name="pumukitwizard_simple_upload")
     */
    public function uploadAction(Request $request, Series $series): Response
    {
        $priority = 2;
        $profile = $this->getDefaultMasterProfile();
        $description = [];
        $language = $request->request->get('language', $request->getLocale());
        $file = $request->files->get('resource');

        try {
            if (!$file) {
                throw new \Exception('No file found');
            }

            if (!$file->isValid()) {
                throw new \Exception($file->getErrorMessage());
            }

            $filePath = $file->getPathname();

            try {
                // exception if is not a mediafile (video or audio)
                $duration = $this->inspectionFfprobeService->getDuration($filePath);
            } catch (\Exception $e) {
                throw new \Exception('The file is not a valid video or audio file');
            }

            if (0 === $duration) {
                throw new \Exception('The file is not a valid video or audio file (duration is zero)');
            }

            $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $multimediaObject = $this->createMultimediaObject($title, $series);
            $multimediaObject->setDuration($duration);

            $this->jobService->createTrackFromLocalHardDrive(
                $multimediaObject,
                $file,
                $profile,
                $priority,
                $language,
                $description,
                [],
                $duration,
                JobService::ADD_JOB_NOT_CHECKS
            );
        } catch (\Exception $e) {
            throw $e;
        }

        $this->pumukitSchemaSortedMultimediaObjectService->reorder($series);

        return $this->redirectToRoute('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()]);
    }

    /**
     * @Route("/embedindex", methods={"GET"}, name="pumukitwizard_simple_embedindex")
     */
    public function embedIndexAction(Request $request): Response
    {
        $seriesId = $request->get('series');
        $externalData = $request->get('externalData');
        $series = $this->objectManager->getRepository(Series::class)->find($seriesId);
        if (!$series) {
            $series = $this->getSeriesByExternalData($externalData);
        }

        $canAccessSeries = null !== $series
                           && $this->seriesServices->canUserAccessSeries($this->getUser(), $series);
        $licenseContent = $this->licenseService->getLicenseContent($request->getLocale());
        $languages = CustomLanguageType::getLanguageNames($this->pumukitCustomLanguages, $this->translator);

        $seriesI18nTitle = [];
        if ($series) {
            $seriesI18nTitle = $series->getI18nTitle();
        } elseif (isset($externalData['title'])) {
            $seriesI18nTitle = $externalData['title'];
        }

        return $this->render(
            '@PumukitWizard/Simple/embedindex.html.twig',
            [
                'can_access_series' => $canAccessSeries,
                'series' => $series,
                'languages' => $languages,
                'show_license' => $this->licenseService->isEnabled(),
                'license_text' => $licenseContent,
                'externalData' => $externalData,
                'show_simple_mm_title' => $this->pumukitWizardShowSimpleMmTitle,
                'show_simple_series_title' => $this->pumukitWizardShowSimpleSeriesTitle,
                'series_i18n_title' => $seriesI18nTitle,
            ]
        );
    }

    /**
     * @Route("/embedupload", methods={"GET","POST"},name="pumukitwizard_simple_embedupload")
     */
    public function embedUploadAction(Request $request): JsonResponse
    {
        $seriesId = $request->get('id');
        $series = $this->objectManager->getRepository(Series::class)->find($seriesId);
        $externalData = $request->get('externalData');
        if (!$series) {
            $series = $this->getSeriesByExternalData($externalData);
        }

        $priority = 2;
        $profile = $this->getDefaultMasterProfile();
        $description = [];
        $language = $request->request->get('language', $request->getLocale());
        $file = $request->files->get('resource');

        if (is_array($file)) {
            $file = $file[0];
        }

        try {
            if (!$file) {
                $response = [
                    'status' => Response::HTTP_BAD_REQUEST,
                    'errorMessage' => $this->translator->trans('No file found'),
                ];

                return new JsonResponse($response);
            }

            if (!$file->isValid()) {
                $response = [
                    'status' => Response::HTTP_BAD_REQUEST,
                    'errorMessage' => $this->translator->trans($file->getErrorMessage()),
                ];

                return new JsonResponse($response);
            }

            $filePath = $file->getPathname();

            try {
                // exception if is not a mediafile (video or audio)
                $duration = $this->inspectionFfprobeService->getDuration($filePath);
            } catch (\Exception $e) {
                $response = [
                    'status' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                    'errorMessage' => $this->translator->trans('The file is not a valid video or audio file'),
                ];

                return new JsonResponse($response);
            }

            if (0 === $duration) {
                $response = [
                    'status' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                    'errorMessage' => $this->translator->trans(
                        'The file is not a valid video or audio file (duration is zero)'
                    ),
                ];

                return new JsonResponse($response);
            }

            if (!$series) {
                $series = $this->createSeries($externalData);
            }

            $showMmTitle = $this->pumukitWizardShowSimpleMmTitle;
            if ($showMmTitle) {
                $i18nTitle = $request->request->get('multimediaobject_i18n_title', []);
                if (!array_filter($i18nTitle)) {
                    $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $i18nTitle = $this->createI18nTitleFromFile($title);
                }
            } else {
                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $i18nTitle = $this->createI18nTitleFromFile($title);
            }
            $multimediaObject = $this->createMultimediaObjectWithI18nTitle($i18nTitle, $series);
            $multimediaObject->setDuration($duration);

            $multimediaObject = $this->setExternalProperties($multimediaObject, $externalData);

            $this->jobService->createTrackFromLocalHardDrive(
                $multimediaObject,
                $file,
                $profile,
                $priority,
                $language,
                $description,
                [],
                $duration,
                JobService::ADD_JOB_NOT_CHECKS
            );

            $this->formEventDispatcherService->dispatchSubmit(
                $this->getUser(),
                $multimediaObject,
                ['simple' => true, 'externalData' => $externalData]
            );
        } catch (\Exception $e) {
            throw $e;
        }

        $this->pumukitSchemaSortedMultimediaObjectService->reorder($series);

        $response = [
            'url' => $this->generateUrl('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()]),
            'mmId' => $multimediaObject->getId(),
        ];

        return new JsonResponse($response);
    }

    private function createMultimediaObject(string $title, Series $series): MultimediaObject
    {
        $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $this->getUser());

        foreach ($this->locales as $locale) {
            $multimediaObject->setTitle($title, $locale);
        }

        return $multimediaObject;
    }

    private function createMultimediaObjectWithI18nTitle(array $i18nTitle, Series $series): MultimediaObject
    {
        $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $this->getUser());
        if (!array_filter($i18nTitle)) {
            $i18nTitle = $this->factoryService->getDefaultMultimediaObjectI18nTitle();
        }
        $multimediaObject->setI18nTitle($i18nTitle);

        return $multimediaObject;
    }

    private function getSeriesByExternalData(?array $externalData)
    {
        if (isset($externalData['seriesData']['title'])) {
            return $this->objectManager->getRepository(Series::class)->findOneBy(
                [
                    'title' => $externalData['seriesData']['title'],
                    'properties.owners' => $this->getUser()->getId(),
                ]
            );
        }

        return null;
    }

    private function createSeries(?array $externalData): Series
    {
        if (isset($externalData['seriesData']['title'])) {
            return $this->factoryService->createSeries($this->getUser(), $externalData['seriesData']['title']);
        }

        return $this->factoryService->createSeries($this->getUser());
    }

    private function createI18nTitleFromFile(string $title): array
    {
        $i18nTitle = [];
        foreach ($this->locales as $locale) {
            $i18nTitle[$locale] = $title;
        }

        return $i18nTitle;
    }

    private function setExternalProperties(MultimediaObject $multimediaObject, ?array $externalData): MultimediaObject
    {
        if (isset($externalData['mmobjData']['properties'])) {
            foreach ($externalData['mmobjData']['properties'] as $key => $value) {
                $multimediaObject->setProperty($key, $value);
            }

            $this->objectManager->persist($multimediaObject);
            $this->objectManager->flush();
        }

        return $multimediaObject;
    }

    private function getDefaultMasterProfile()
    {
        if ($this->pumukitWizardSimpleDefaultMasterProfile) {
            return $this->pumukitWizardSimpleDefaultMasterProfile;
        }

        return $this->profileService->getDefaultMasterProfile();
    }
}
