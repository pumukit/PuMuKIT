<?php

namespace Pumukit\WizardBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\SortedMultimediaObjectsService;
use Pumukit\WizardBundle\Services\FormEventDispatcherService;
use Pumukit\WizardBundle\Services\LicenseService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_WIZARD_UPLOAD')")
 */
class SimpleController extends AbstractController
{
    /**
     * @Template("PumukitWizardBundle:Simple:index.html.twig")
     */
    public function indexAction(Request $request, LicenseService $licenseService, TranslatorInterface $translator, Series $series, array $pumukitCustomLanguages): array
    {
        $licenseContent = $licenseService->getLicenseContent($request->getLocale());

        $languages = CustomLanguageType::getLanguageNames($pumukitCustomLanguages, $translator);

        return [
            'series' => $series,
            'languages' => $languages,
            'show_license' => $licenseService->isEnabled(),
            'license_text' => $licenseContent,
        ];
    }

    public function uploadAction(Request $request, JobService $jobService, ProfileService $profileService, FactoryService $factoryService, array $locales, InspectionFfprobeService $inspectionFfprobeService, SortedMultimediaObjectsService $sortedMultimediaObjectsService, Series $series, $pumukitWizardSimpleDefaultMasterProfile)
    {
        $priority = 2;
        $profile = $this->getDefaultMasterProfile($profileService, $pumukitWizardSimpleDefaultMasterProfile);
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
                $duration = $inspectionFfprobeService->getDuration($filePath);
            } catch (\Exception $e) {
                throw new \Exception('The file is not a valid video or audio file');
            }

            if (0 === $duration) {
                throw new \Exception('The file is not a valid video or audio file (duration is zero)');
            }

            $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $multimediaObject = $this->createMultimediaObject($factoryService, $locales, $title, $series);
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

        $sortedMultimediaObjectsService->reorder($series);

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()]));
    }

    /**
     * @Template("PumukitWizardBundle:Simple:embedindex.html.twig")
     */
    public function embedindexAction(Request $request, DocumentManager $documentManager, LicenseService $licenseService, TranslatorInterface $translator, array $pumukitCustomLocales, $pumukitWizardShowSimpleMmTitle, $pumukitWizardShowSimpleSeriesTitle)
    {
        $seriesId = $request->get('series');
        $externalData = $request->get('externalData');
        $series = $documentManager->getRepository(Series::class)->find($seriesId);
        if (!$series) {
            $series = $this->getSeriesByExternalData($documentManager, $externalData);
        }

        $licenseContent = $licenseService->getLicenseContent($request->getLocale());
        $languages = CustomLanguageType::getLanguageNames($pumukitCustomLocales, $translator);

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
            'show_simple_mm_title' => $pumukitWizardShowSimpleMmTitle,
            'show_simple_series_title' => $pumukitWizardShowSimpleSeriesTitle,
            'series_i18n_title' => $seriesI18nTitle,
        ];
    }

    public function embeduploadAction(Request $request, DocumentManager $documentManager, ProfileService $profileService, JobService $jobService, FactoryService $factoryService, InspectionFfprobeService $inspectionFfprobeService, SortedMultimediaObjectsService $sortedMultimediaObjectsService, FormEventDispatcherService $formDispatcher, array $locales, $pumukitWizardSimpleDefaultMasterProfile)
    {
        $seriesId = $request->get('id');
        $series = $documentManager->getRepository(Series::class)->find($seriesId);
        $externalData = $request->get('externalData');
        if (!$series) {
            $series = $this->getSeriesByExternalData($documentManager, $externalData);
        }

        $priority = 2;
        $profile = $this->getDefaultMasterProfile($profileService, $pumukitWizardSimpleDefaultMasterProfile);
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
                $duration = $inspectionFfprobeService->getDuration($filePath);
            } catch (\Exception $e) {
                throw new \Exception('The file is not a valid video or audio file');
            }

            if (0 === $duration) {
                throw new \Exception('The file is not a valid video or audio file (duration is zero)');
            }

            if (!$series) {
                $series = $this->createSeries($factoryService, $externalData);
            }

            $showMmTitle = $this->getParameter('pumukit_wizard.show_simple_mm_title');
            if ($showMmTitle) {
                $i18nTitle = $request->request->get('multimediaobject_i18n_title', []);
                if (!array_filter($i18nTitle)) {
                    $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $i18nTitle = $this->createI18nTitleFromFile($locales, $title);
                }
            } else {
                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $i18nTitle = $this->createI18nTitleFromFile($locales, $title);
            }
            $multimediaObject = $this->createMultimediaObjectWithI18nTitle($factoryService, $i18nTitle, $series);
            $multimediaObject->setDuration($duration);

            $multimediaObject = $this->setExternalProperties($documentManager, $multimediaObject, $externalData);

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

            $formDispatcher->dispatchSubmit($this->getUser(), $multimediaObject, ['simple' => true, 'externalData' => $externalData]);
        } catch (\Exception $e) {
            throw $e;
        }

        $sortedMultimediaObjectsService->reorder($series);

        $response = [
            'url' => $this->generateUrl('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()]),
            'mmId' => $multimediaObject->getId(),
        ];

        return new JsonResponse($response);
    }

    private function createMultimediaObject(FactoryService $factoryService, array $locales, string $title, Series $series): MultimediaObject
    {
        $multimediaObject = $factoryService->createMultimediaObject($series, true, $this->getUser());

        foreach ($locales as $locale) {
            $multimediaObject->setTitle($title, $locale);
        }

        return $multimediaObject;
    }

    private function createMultimediaObjectWithI18nTitle(FactoryService $factoryService, $i18nTitle, Series $series): MultimediaObject
    {
        $multimediaObject = $factoryService->createMultimediaObject($series, true, $this->getUser());
        if (!array_filter($i18nTitle)) {
            $i18nTitle = $factoryService->getDefaultMultimediaObjectI18nTitle();
        }
        $multimediaObject->setI18nTitle($i18nTitle);

        return $multimediaObject;
    }

    private function getSeriesByExternalData(DocumentManager $documentManager, array $externalData)
    {
        if (isset($externalData['seriesData']['title'])) {
            return $documentManager->getRepository(Series::class)->findOneBy([
                'title' => $externalData['seriesData']['title'],
                'properties.owners' => $this->getUser()->getId(),
            ]);
        }

        return null;
    }

    private function createSeries(FactoryService $factoryService, array $externalData): Series
    {
        if (isset($externalData['seriesData']['title'])) {
            return $factoryService->createSeries($this->getUser(), $externalData['seriesData']['title']);
        }

        return $factoryService->createSeries($this->getUser());
    }

    private function createI18nTitleFromFile(array $locales, string $title): array
    {
        $i18nTitle = [];
        foreach ($locales as $locale) {
            $i18nTitle[$locale] = $title;
        }

        return $i18nTitle;
    }

    private function setExternalProperties(DocumentManager $documentManager, MultimediaObject $multimediaObject, array $externalData): MultimediaObject
    {
        if (isset($externalData['mmobjData']['properties'])) {
            foreach ($externalData['mmobjData']['properties'] as $key => $value) {
                $multimediaObject->setProperty($key, $value);
            }

            $documentManager->persist($multimediaObject);
            $documentManager->flush();
        }

        return $multimediaObject;
    }

    private function getDefaultMasterProfile(ProfileService $profileService, $pumukitWizardSimpleDefaultMasterProfile = null)
    {
        if ($pumukitWizardSimpleDefaultMasterProfile) {
            return $pumukitWizardSimpleDefaultMasterProfile;
        }

        return $profileService->getDefaultMasterProfile();
    }
}
