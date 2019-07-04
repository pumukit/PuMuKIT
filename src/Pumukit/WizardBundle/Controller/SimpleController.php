<?php

namespace Pumukit\WizardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;

/**
 * @Security("is_granted('ROLE_ACCESS_WIZARD_UPLOAD')")
 */
class SimpleController extends Controller
{
    /**
     * @param Series  $series
     * @param Request $request
     *
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

    /**
     * @param Series  $series
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Exception
     */
    public function uploadAction(Series $series, Request $request)
    {
        $jobService = $this->get('pumukitencoder.job');
        $inspectionService = $this->get('pumukit.inspection');

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
                //exception if is not a mediafile (video or audio)
                $duration = $inspectionService->getDuration($filePath);
            } catch (\Exception $e) {
                throw new \Exception('The file is not a valid video or audio file');
            }

            if (0 == $duration) {
                throw new \Exception('The file is not a valid video or audio file (duration is zero)');
            }

            $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $multimediaObject = $this->createMultimediaObject($title, $series);
            $multimediaObject->setDuration($duration);

            $jobService->createTrackFromLocalHardDrive(
                $multimediaObject, $file, $profile, $priority, $language, $description,
                [], $duration, JobService::ADD_JOB_NOT_CHECKS
            );
        } catch (\Exception $e) {
            throw $e;
        }

        $this->get('pumukitschema.sorted_multimedia_object')->reorder($series);

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()]));
    }

    /**
     * @param Request $request
     *
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
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
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

        try {
            if (!$file) {
                throw new \Exception('No file found');
            }

            if (!$file->isValid()) {
                throw new \Exception($file->getErrorMessage());
            }

            $filePath = $file->getPathname();

            try {
                //exception if is not a mediafile (video or audio)
                $duration = $inspectionService->getDuration($filePath);
            } catch (\Exception $e) {
                throw new \Exception('The file is not a valid video or audio file');
            }

            if (0 == $duration) {
                throw new \Exception('The file is not a valid video or audio file (duration is zero)');
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
                $multimediaObject, $file, $profile, $priority, $language, $description,
                [], $duration, JobService::ADD_JOB_NOT_CHECKS
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
     * @param        $title
     * @param Series $series
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
     * @param        $i18nTitle
     * @param Series $series
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
     * @param $seriesId
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
     * @param $externalData
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
     * @param $externalData
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
     * @param $title
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
     * @param MultimediaObject $multimediaObject
     * @param                  $externalData
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
