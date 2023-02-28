<?php

namespace Pumukit\WizardBundle\Controller;

use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("is_granted('ROLE_ACCESS_WIZARD_UPLOAD')")
 */
class SimpleController extends Controller
{
    /**
     * @return array
     *
     * @Template("PumukitWizardBundle:Simple:index.html.twig")
     */
    public function indexAction(Series $series, Request $request)
    {
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseContent = $licenseService->getLicenseContent($request->getLocale());

        $languages = CustomLanguageType::getLanguageNames($this->container->getParameter('pumukit.customlanguages'), $this->get('translator'));

        return [
            'series' => $series,
            'languages' => $languages,
            'show_license' => $licenseService->isEnabled(),
            'license_text' => $licenseContent,
        ];
    }

    public function uploadAction(Series $series, Request $request)
    {
        $jobService = $this->get('pumukitencoder.job');
        $inspectionService = $this->get('pumukit.inspection');

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
                    'errorMessage' => $this->get('translator')->trans('No file found'),
                ];

                return new JsonResponse($response);
            }

            if (!$file->isValid()) {
                $response = [
                    'status' => Response::HTTP_BAD_REQUEST,
                    'errorMessage' => $this->get('translator')->trans($file->getErrorMessage()),
                ];

                return new JsonResponse($response);
            }

            $filePath = $file->getPathname();

            try {
                $duration = $inspectionService->getDuration($filePath);
            } catch (\Exception $e) {
                $response = [
                    'status' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                    'errorMessage' => $this->get('translator')->trans('The file is not a valid video or audio file'),
                ];

                return new JsonResponse($response);
            }

            if (0 === (int) $duration) {
                $response = [
                    'status' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                    'errorMessage' => $this->get('translator')->trans('The file is not a valid video or audio file (duration is zero)'),
                ];

                return new JsonResponse($response);
            }

            $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $multimediaObject = $this->createMultimediaObject($title, $series);
            $multimediaObject->setDuration($duration);

            $jobService->createTrackFromLocalHardDrive(
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

        $this->get('pumukitschema.sorted_multimedia_object')->reorder($series);

        $response = [
            'url' => $this->generateUrl('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()]),
        ];

        return new JsonResponse($response);
    }

    /**
     * @return array
     *
     * @Template("PumukitWizardBundle:Simple:embedindex.html.twig")
     */
    public function embedindexAction(Request $request)
    {
        $seriesId = $request->get('series');
        $externalData = $request->get('externalData');
        $series = $this->getSeries($seriesId);
        if (!$series) {
            $series = $this->getSeriesByExternalData($externalData);
        }

        $canAccessSeries = null !== $series && $this->get('pumukitschema.series')->canUserAccessSeries($this->getUser(), $series);

        $licenseService = $this->get('pumukit_wizard.license');
        $licenseContent = $licenseService->getLicenseContent($request->getLocale());

        $languages = CustomLanguageType::getLanguageNames($this->container->getParameter('pumukit.customlanguages'), $this->get('translator'));

        $showMmTitle = $this->getParameter('pumukit_wizard.show_simple_mm_title');
        $showSeriesTitle = $this->getParameter('pumukit_wizard.show_simple_series_title');

        $seriesI18nTitle = [];
        if ($series) {
            $seriesI18nTitle = $series->getI18nTitle();
        } elseif (isset($externalData['title'])) {
            $seriesI18nTitle = $externalData['title'];
        }

        return [
            'can_access_series' => $canAccessSeries,
            'series' => $series,
            'languages' => $languages,
            'show_license' => $licenseService->isEnabled(),
            'license_text' => $licenseContent,
            'externalData' => $externalData,
            'show_simple_mm_title' => $showMmTitle,
            'show_simple_series_title' => $showSeriesTitle,
            'series_i18n_title' => $seriesI18nTitle,
        ];
    }

    /**
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function embeduploadAction(Request $request)
    {
        $seriesId = $request->get('id');
        $series = $this->getSeries($seriesId);
        $externalData = $request->get('externalData');
        if (!$series) {
            $series = $this->getSeriesByExternalData($externalData);
        }

        $jobService = $this->get('pumukitencoder.job');
        $inspectionService = $this->get('pumukit.inspection');

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
                    'errorMessage' => $this->get('translator')->trans('No file found'),
                ];

                return new JsonResponse($response);
            }

            if (!$file->isValid()) {
                $response = [
                    'status' => Response::HTTP_BAD_REQUEST,
                    'errorMessage' => $this->get('translator')->trans($file->getErrorMessage()),
                ];

                return new JsonResponse($response);
            }

            $filePath = $file->getPathname();

            try {
                $duration = $inspectionService->getDuration($filePath);
            } catch (\Exception $e) {
                $response = [
                    'status' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                    'errorMessage' => $this->get('translator')->trans('The file is not a valid video or audio file'),
                ];

                return new JsonResponse($response);
            }

            if (0 === (int) $duration) {
                $response = [
                    'status' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                    'errorMessage' => $this->get('translator')->trans('The file is not a valid video or audio file (duration is zero)'),
                ];

                return new JsonResponse($response);
            }

            if (!$series) {
                $series = $this->createSeries($externalData);
            }

            $showMmTitle = $this->getParameter('pumukit_wizard.show_simple_mm_title');
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

            $jobService->createTrackFromLocalHardDrive(
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

            $formDispatcher = $this->get('pumukit_wizard.form_dispatcher');
            $formDispatcher->dispatchSubmit($this->getUser(), $multimediaObject, ['simple' => true, 'externalData' => $externalData]);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->get('pumukitschema.sorted_multimedia_object')->reorder($series);

        $response = [
            'url' => $this->generateUrl('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()]),
            'mmId' => $multimediaObject->getId(),
        ];

        return new JsonResponse($response);
    }

    /**
     * Create Multimedia Object.
     *
     * @param string $title
     *
     * @return MultimediaObject
     */
    private function createMultimediaObject($title, Series $series)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $multimediaObject = $factoryService->createMultimediaObject($series, true, $this->getUser());

        foreach ($this->container->getParameter('pumukit.locales') as $locale) {
            $multimediaObject->setTitle($title, $locale);
        }

        return $multimediaObject;
    }

    /**
     * Create Multimedia Object.
     *
     * @param array $i18nTitle
     *
     * @return MultimediaObject
     */
    private function createMultimediaObjectWithI18nTitle($i18nTitle, Series $series)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $multimediaObject = $factoryService->createMultimediaObject($series, true, $this->getUser());
        if (!array_filter($i18nTitle)) {
            $i18nTitle = $factoryService->getDefaultMultimediaObjectI18nTitle();
        }
        $multimediaObject->setI18nTitle($i18nTitle);

        return $multimediaObject;
    }

    /**
     * Get Series from id.
     *
     * @param \MongoId|string $seriesId
     *
     * @return mixed
     */
    private function getSeries($seriesId)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository(Series::class);

        return $repo->find($seriesId);
    }

    /**
     * Get Series by external data.
     *
     * @param array $externalData
     *
     * @return object|null
     */
    private function getSeriesByExternalData($externalData)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository(Series::class);

        if (isset($externalData['seriesData']['title'])) {
            return $repo->findOneBy(['title' => $externalData['seriesData']['title'], 'properties.owners' => $this->getUser()->getId()]);
        }

        return null;
    }

    /**
     * @param array $externalData
     *
     * @return Series
     */
    private function createSeries($externalData)
    {
        $factoryService = $this->get('pumukitschema.factory');
        if (isset($externalData['seriesData']['title'])) {
            $series = $factoryService->createSeries($this->getUser(), $externalData['seriesData']['title']);
        } else {
            $series = $factoryService->createSeries($this->getUser());
        }

        return $series;
    }

    /**
     * @param string $title
     *
     * @return array
     */
    private function createI18nTitleFromFile($title)
    {
        $i18nTitle = [];
        foreach ($this->container->getParameter('pumukit.locales') as $locale) {
            $i18nTitle[$locale] = $title;
        }

        return $i18nTitle;
    }

    /**
     * @param array $externalData
     *
     * @return MultimediaObject
     */
    private function setExternalProperties(MultimediaObject $multimediaObject, $externalData)
    {
        if (isset($externalData['mmobjData']['properties'])) {
            foreach ($externalData['mmobjData']['properties'] as $key => $value) {
                $multimediaObject->setProperty($key, $value);
            }
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $dm->persist($multimediaObject);
            $dm->flush();
        }

        return $multimediaObject;
    }

    /**
     * @return mixed|null
     */
    private function getDefaultMasterProfile()
    {
        if ($this->container->hasParameter('pumukit_wizard.simple_default_master_profile')) {
            return $this->container->getParameter('pumukit_wizard.simple_default_master_profile');
        }

        return $this->get('pumukitencoder.profile')->getDefaultMasterProfile();
    }
}
