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
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\SortedMultimediaObjectsService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\WizardBundle\Services\FormEventDispatcherService;
use Pumukit\WizardBundle\Services\LicenseService;
use Pumukit\WizardBundle\Services\WizardService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_WIZARD_UPLOAD')")
 * @Route("/admin/wizard")
 */
class DefaultController extends AbstractController
{
    public const SERIES_LIMIT = 30;

    private $objectManager;
    private $licenseService;
    private $pumukitWizardShowTags;
    private $pumukitWizardShowObjectLicense;
    private $pumukitWizardMandatoryTitle;
    private $pumukitWizardReuseSeries;
    private $pumukitWizardReuseAdminSeries;
    private $pumukitSchemaDefaultLicense;
    private $pumukitWizardTagParentCod;
    private $pumukitNewAdminLicenses;
    private $profileService;
    private $factoryService;
    private $translator;
    private $tagService;
    private $wizardService;
    private $pumukitSchemaSortedMultimediaObjectService;
    private $jobService;
    private $inspectionFfprobeService;
    private $formEventDispatcherService;
    private $authorizationChecker;
    private $locales;
    private $pumukitCustomLanguages;

    public function __construct(
        DocumentManager $objectManager,
        LicenseService $licenseService,
        ProfileService $profileService,
        FactoryService $factoryService,
        TranslatorInterface $translator,
        TagService $tagService,
        WizardService $wizardService,
        SortedMultimediaObjectsService $pumukitSchemaSortedMultimediaObjectService,
        JobService $jobService,
        InspectionFfprobeService $inspectionFfprobeService,
        FormEventDispatcherService $formEventDispatcherService,
        AuthorizationCheckerInterface $authorizationChecker,
        bool $pumukitWizardShowTags,
        bool $pumukitWizardShowObjectLicense,
        string $pumukitWizardMandatoryTitle,
        bool $pumukitWizardReuseSeries,
        bool $pumukitWizardReuseAdminSeries,
        string $pumukitWizardTagParentCod,
        string $pumukitSchemaDefaultLicense,
        $pumukitNewAdminLicenses,
        array $locales,
        array $pumukitCustomLanguages
    ) {
        $this->objectManager = $objectManager;
        $this->licenseService = $licenseService;
        $this->pumukitWizardShowTags = $pumukitWizardShowTags;
        $this->pumukitWizardShowObjectLicense = $pumukitWizardShowObjectLicense;
        $this->pumukitWizardMandatoryTitle = $pumukitWizardMandatoryTitle;
        $this->pumukitWizardReuseSeries = $pumukitWizardReuseSeries;
        $this->pumukitWizardReuseAdminSeries = $pumukitWizardReuseAdminSeries;
        $this->pumukitSchemaDefaultLicense = $pumukitSchemaDefaultLicense;
        $this->pumukitWizardTagParentCod = $pumukitWizardTagParentCod;
        $this->pumukitNewAdminLicenses = $pumukitNewAdminLicenses;
        $this->profileService = $profileService;
        $this->factoryService = $factoryService;
        $this->translator = $translator;
        $this->tagService = $tagService;
        $this->wizardService = $wizardService;
        $this->pumukitSchemaSortedMultimediaObjectService = $pumukitSchemaSortedMultimediaObjectService;
        $this->jobService = $jobService;
        $this->authorizationChecker = $authorizationChecker;
        $this->inspectionFfprobeService = $inspectionFfprobeService;
        $this->formEventDispatcherService = $formEventDispatcherService;
        $this->locales = $locales;
        $this->pumukitCustomLanguages = $pumukitCustomLanguages;
    }

    /**
     * @Route("/license", methods={"GET","POST"}, name="pumukitwizard_default_license")
     */
    public function licenseAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        if (!$this->licenseService->isEnabled()) {
            if ($sameSeries) {
                return $this->redirect($this->generateUrl('pumukitwizard_default_type', ['pumukitwizard_form_data' => $formData, 'id' => $formData['series']['id'], 'same_series' => $sameSeries]));
            }

            return $this->redirect($this->generateUrl('pumukitwizard_default_series', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        $licenseContent = $this->licenseService->getLicenseContent($request->getLocale());

        return $this->render('@PumukitWizard/Default/license.html.twig', [
            'license_text' => $licenseContent,
            'form_data' => $formData,
            'show_series' => $showSeries,
            'same_series' => $sameSeries,
            'license_enable' => $this->licenseService->isEnabled(),
            'show_tags' => $this->pumukitWizardShowTags,
            'show_object_license' => $this->pumukitWizardShowObjectLicense,
        ]);
    }

    /**
     * @Route("/", methods={"GET"}, name="pumukitwizard_default_index")
     * @Route("/series", methods={"GET","POST"}, name="pumukitwizard_default_series")
     */
    public function seriesAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseEnabledAndAccepted = $this->licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        $mandatoryTitle = $this->pumukitWizardMandatoryTitle ? 1 : 0;
        $userSeries = [];

        if ($this->pumukitWizardReuseSeries) {
            $user = $this->getUser();
            $reuseAdminSeries = $this->pumukitWizardReuseAdminSeries;
            $userSeries = $this->objectManager->getRepository(Series::class)->findUserSeries($user, $reuseAdminSeries);

            usort($userSeries, static function ($a, $b) use ($request) {
                return strcmp($a['_id']['title'][$request->getLocale()], $b['_id']['title'][$request->getLocale()]);
            });
        }

        return $this->render('@PumukitWizard/Default/series.html.twig', [
            'form_data' => $formData,
            'license_enable' => $this->licenseService->isEnabled(),
            'mandatory_title' => $mandatoryTitle,
            'reuse_series' => $this->pumukitWizardReuseSeries,
            'same_series' => $sameSeries,
            'user_series' => $userSeries,
            'show_tags' => $this->pumukitWizardShowTags,
            'show_object_license' => $this->pumukitWizardShowObjectLicense,
        ]);
    }

    /**
     * @Route("/type/{id}", methods={"GET","POST"}, name="pumukitwizard_default_type")
     */
    public function typeAction(Request $request, string $id)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        if (!isset($formData['series']['new'])) {
            $formData['series']['new'] = 0;
        }
        $newSeries = (bool) $formData['series']['new'];
        if (!$newSeries && isset($formData['series']['reuse']['id'])) {
            $id = $formData['series']['reuse']['id'];
            $formData['series']['id'] = $id;
        } elseif ($newSeries && isset($formData['series']['id'])) {
            if (!$id || ('null' === $id)) {
                $id = null;
                $formData['series']['id'] = null;
                $formData['series']['reuse']['id'] = null;
            } else {
                $formData['series']['id'] = $id;
                $formData['series']['reuse']['id'] = $id;
            }
        }
        $series = $this->objectManager->getRepository(Series::class)->find($id);
        if ($series) {
            $formData = $this->completeFormWithSeries($formData, $series);
        }
        if (false === $this->authorizationChecker->isGranted(Permission::ACCESS_INBOX)) {
            $formData['series']['id'] = $id;
            $formData['type']['option'] = 'single';

            return $this->redirect($this->generateUrl('pumukitwizard_default_option', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        $licenseEnabledAndAccepted = $this->licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['show_series' => $showSeries, 'pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        return $this->render('@PumukitWizard/Default/type.html.twig', [
            'series_id' => $id,
            'form_data' => $formData,
            'show_series' => $showSeries,
            'license_enable' => $this->licenseService->isEnabled(),
            'same_series' => $sameSeries,
            'show_tags' => $this->pumukitWizardShowTags,
            'show_object_license' => $this->pumukitWizardShowObjectLicense,
        ]);
    }

    /**
     * @Route("/option", methods={"GET","POST"}, name="pumukitwizard_default_option")
     */
    public function optionAction(Request $request): RedirectResponse
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseEnabledAndAccepted = $this->licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        if (('multiple' === $formData['type']['option']) && (false !== $this->authorizationChecker->isGranted(Permission::ACCESS_INBOX))) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_track', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        return $this->redirect($this->generateUrl('pumukitwizard_default_multimediaobject', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
    }

    /**
     * @Route("/multimediaobject", methods={"GET","POST"}, name="pumukitwizard_default_multimediaobject")
     */
    public function multimediaobjectAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        if (!isset($formData['series']['new'])) {
            $formData['series']['new'] = 0;
        }
        $newSeries = (bool) $formData['series']['new'];
        if (!$newSeries && isset($formData['series']['reuse']['id'])) {
            $id = $formData['series']['reuse']['id'];
            $formData['series']['id'] = $id;
            $series = $this->objectManager->getRepository(Series::class)->find($id);
            if ($series) {
                $formData = $this->completeFormWithSeries($formData, $series);
            }
        } elseif ($newSeries && isset($formData['series']['id'])) {
            $formData['series']['id'] = null;
            $formData['series']['reuse']['id'] = null;
        }
        $licenseEnabledAndAccepted = $this->licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        $availableTags = [];
        if ($this->pumukitWizardShowTags) {
            $tagParent = $this->objectManager->getRepository(Tag::class)->findOneBy(['cod' => $this->pumukitWizardTagParentCod]);
            if ($tagParent) {
                $availableTags = $tagParent->getChildren();
            }
        }
        $objectDefaultLicense = null;
        $objectAvailableLicenses = null;
        if ($this->pumukitWizardShowObjectLicense) {
            $objectDefaultLicense = $this->pumukitSchemaDefaultLicense;
            $objectAvailableLicenses = $this->pumukitNewAdminLicenses;
        }
        $mandatoryTitle = $this->pumukitWizardMandatoryTitle ? 1 : 0;

        return $this->render('@PumukitWizard/Default/multimediaobject.html.twig', [
            'form_data' => $formData,
            'license_enable' => $this->licenseService->isEnabled(),
            'show_tags' => $this->pumukitWizardShowTags,
            'available_tags' => $availableTags,
            'show_object_license' => $this->pumukitWizardShowObjectLicense,
            'object_default_license' => $objectDefaultLicense,
            'object_available_licenses' => $objectAvailableLicenses,
            'mandatory_title' => $mandatoryTitle,
            'same_series' => $sameSeries,
            'show_series' => $showSeries,
        ]);
    }

    /**
     * @Route("/track", methods={"GET","POST"}, name="pumukitwizard_default_track")
     */
    public function trackAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseEnabledAndAccepted = $this->licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        $masterProfiles = $this->profileService->getMasterProfiles(true);
        $pubChannelsTags = $this->factoryService->getTagsByCod('PUBCHANNELS', true);

        foreach ($pubChannelsTags as $key => $pubTag) {
            if ($pubTag->getProperty('hide_in_tag_group')) {
                unset($pubChannelsTags[$key]);
            }
        }

        $languages = CustomLanguageType::getLanguageNames($this->pumukitCustomLanguages, $this->translator);

        $status = [];
        $statusSelected = false;
        if ($this->isGranted(Permission::ACCESS_PUBLICATION_TAB) && $this->isGranted(Permission::CHANGE_MMOBJECT_STATUS)) {
            $status = [
                MultimediaObject::STATUS_PUBLISHED => 'Published',
                MultimediaObject::STATUS_HIDDEN => 'Hidden',
                MultimediaObject::STATUS_BLOCKED => 'Blocked',
            ];

            if ($this->isGranted(Permission::INIT_STATUS_PUBLISHED)) {
                $statusSelected = MultimediaObject::STATUS_PUBLISHED;
            } elseif ($this->isGranted(Permission::INIT_STATUS_HIDDEN)) {
                $statusSelected = MultimediaObject::STATUS_HIDDEN;
            } else {
                $statusSelected = MultimediaObject::STATUS_BLOCKED;
            }
        }

        return $this->render('@PumukitWizard/Default/track.html.twig', [
            'form_data' => $formData,
            'status' => $status,
            'statusSelected' => $statusSelected,
            'master_profiles' => $masterProfiles,
            'pub_channels' => $pubChannelsTags,
            'languages' => $languages,
            'license_enable' => $this->licenseService->isEnabled(),
            'show_tags' => $this->pumukitWizardShowTags,
            'show_object_license' => $this->pumukitWizardShowObjectLicense,
            'same_series' => $sameSeries,
            'show_series' => $showSeries,
        ]);
    }

    /**
     * @Route("/upload", methods={"GET","POST"}, name="pumukitwizard_default_upload")
     */
    public function uploadAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseEnabledAndAccepted = $this->licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', [
                'pumukitwizard_form_data' => $formData,
                'same_series' => $sameSeries,
            ]));
        }

        if (!$formData) {
            $endPage = $this->generateUrl('pumukitwizard_default_error', [
                'errormessage' => 'Something was wrong. No data received',
                'option' => null,
                'show_series' => $showSeries,
                'same_series' => $sameSeries,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse([
                'endPage' => $endPage,
            ]);
        }

        $series = null;
        $multimediaObject = null;
        $mmId = null;

        $seriesData = $this->getKeyData('series', $formData);

        $seriesId = $this->getKeyData('id', $seriesData);

        $typeData = $this->getKeyData('type', $formData);
        $trackData = $this->getKeyData('track', $formData);

        if (!$this->isGranted('ROLE_DISABLED_WIZARD_TRACK_PROFILES')) {
            $profile = $this->getKeyData('profile', $trackData, null);
        } else {
            $profile = $this->profileService->getDefaultMasterProfile();
        }
        if (!$profile) {
            throw new \Exception('Not exists master profile');
        }

        $priority = $this->getKeyData('priority', $trackData, 2);
        $language = $this->getKeyData('language', $trackData);
        $description = $this->getKeyData('description', $trackData);

        $pubchannel = $this->getKeyData('pubchannel', $trackData);

        $status = null;
        if (isset($formData['multimediaobject']['status'])) {
            $status = $formData['multimediaobject']['status'];
        }

        $option = $this->getKeyData('option', $typeData);

        try {
            if ('single' === $option) {
                $filePath = null;
                $filetype = $this->getKeyData('filetype', $trackData);
                $resourceFile = null;
                if ('file' === $filetype) {
                    $resourceFile = $request->files->get('resource');
                    $resourceFile = reset($resourceFile);
                    if ($resourceFile) {
                        if (!$resourceFile->isValid()) {
                            throw new \Exception($request->files->get('resource')->getErrorMessage());
                        }
                        $filePath = $resourceFile->getPathname();
                    }
                } elseif ('inbox' === $filetype) {
                    $filePath = $request->get('file');
                }

                if (!$filePath) {
                    throw new \Exception('Not uploaded file or inbox path');
                }

                try {
                    $duration = $this->inspectionFfprobeService->getDuration($filePath);
                } catch (\Exception $e) {
                    throw new \Exception('The file is not a valid video or audio file');
                }

                if (0 == $duration) {
                    throw new \Exception('The file is not a valid video or audio file (duration is zero)');
                }

                $series = $this->getSeries($seriesData);
                $multimediaObjectData = $this->getKeyData('multimediaobject', $formData);

                $i18nTitle = $this->getKeyData('i18n_title', $multimediaObjectData);
                if (empty(array_filter($i18nTitle))) {
                    $multimediaObjectData = $this->getDefaultFieldValuesInData($multimediaObjectData, 'i18n_title', 'New', true);
                }

                $multimediaObject = $this->createMultimediaObject($multimediaObjectData, $series);
                $multimediaObject->setDuration($duration);

                if ($this->pumukitWizardShowObjectLicense) {
                    $license = $this->getKeyData('license', $formData['multimediaobject']);
                    if ($license && ('0' !== $license)) {
                        $multimediaObject = $this->setData($multimediaObject, $formData['multimediaobject'], ['license']);
                    }
                }

                $this->formEventDispatcherService->dispatchSubmit($this->getUser(), $multimediaObject, $formData);

                if ('file' === $filetype) {
                    $multimediaObject = $this->jobService->createTrackFromLocalHardDrive(
                        $multimediaObject,
                        $resourceFile,
                        $profile,
                        $priority,
                        $language,
                        $description,
                        [],
                        $duration,
                        JobService::ADD_JOB_NOT_CHECKS
                    );
                } elseif ('inbox' === $filetype) {
                    $this->denyAccessUnlessGranted(Permission::ACCESS_INBOX);
                    $multimediaObject = $this->jobService->createTrackFromInboxOnServer(
                        $multimediaObject,
                        $request->get('file'),
                        $profile,
                        $priority,
                        $language,
                        $description,
                        [],
                        $duration,
                        JobService::ADD_JOB_NOT_CHECKS
                    );
                }

                if ($multimediaObject && $pubchannel) {
                    foreach ($pubchannel as $tagCode => $valueOn) {
                        $this->addTagToMultimediaObjectByCode($multimediaObject, $tagCode);
                    }
                }

                if ($multimediaObject && isset($status)) {
                    $multimediaObject->setStatus((int) $status);
                }

                if ($this->pumukitWizardShowTags) {
                    $tagCode = $this->getKeyData('tag', $formData['multimediaobject']);
                    if ('0' !== $tagCode) {
                        $this->addTagToMultimediaObjectByCode($multimediaObject, $tagCode);
                    }
                }
            } elseif ('multiple' === $option) {
                $this->denyAccessUnlessGranted(Permission::ACCESS_INBOX);

                $series = $this->wizardService->uploadMultipleFiles(
                    $this->getUser()->getId(),
                    $request->get('file'),
                    $seriesData,
                    [
                        'status' => $status,
                        'pubChannel' => $pubchannel,
                        'profile' => $profile,
                        'priority' => $priority,
                        'language' => $language,
                        'description' => $description,
                    ]
                );
            }
            $this->objectManager->flush();
        } catch (\Exception $e) {
            $endPage = $this->generateUrl('pumukitwizard_default_error', [
                'errormessage' => preg_replace("/\r|\n/", '', $e->getMessage()),
                'option' => $option,
                'seriesId' => $seriesId,
                'mmId' => null,
                'show_series' => $showSeries,
                'same_series' => $sameSeries,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse([
                'endPage' => $endPage,
            ]);
        }

        if ($series) {
            $seriesId = $series->getId();
            $this->pumukitSchemaSortedMultimediaObjectService->reorder($series);
        }

        if ($multimediaObject) {
            $mmId = $multimediaObject->getId();
        }

        $endPage = $this->generateUrl('pumukitwizard_default_end', [
            'seriesId' => $seriesId,
            'mmId' => $mmId,
            'option' => $option,
            'show_series' => $showSeries,
            'same_series' => $sameSeries,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse([
            'uploaded' => 'success',
            'message' => 'Track(s) added',
            'endPage' => $endPage,
        ]);
    }

    /**
     * @Route("/end", methods={"GET","POST"}, name="pumukitwizard_default_end")
     */
    public function endAction(Request $request)
    {
        $series = $this->objectManager->getRepository(Series::class)->find($request->get('seriesId'));
        $multimediaObject = $this->objectManager->getRepository(MultimediaObject::class)->find($request->get('mmId'));
        $option = $request->get('option');
        $showSeries = $request->get('show_series');

        $licenseEnabled = $this->licenseService->isEnabled();

        $sameSeries = $request->get('same_series', false);

        return $this->render('@PumukitWizard/Default/end.html.twig', [
            'message' => 'success it seems',
            'series' => $series,
            'mm' => $multimediaObject,
            'option' => $option,
            'show_series' => $showSeries,
            'license_enabled' => $licenseEnabled,
            'same_series' => $sameSeries,
        ]);
    }

    /**
     * @Route("/error", methods={"GET","POST"}, name="pumukitwizard_default_error")
     */
    public function errorAction(Request $request)
    {
        $errorMessage = $request->get('errormessage');
        $option = $request->get('option');
        $showSeries = $request->get('show_series');

        $series = $this->objectManager->getRepository(Series::class)->find($request->get('seriesId'));
        $sameSeries = $request->get('same_series', false);

        return $this->render('@PumukitWizard/Default/error.html.twig', [
            'series' => $series,
            'message' => $errorMessage,
            'option' => $option,
            'show_series' => $showSeries,
            'same_series' => $sameSeries,
        ]);
    }

    /**
     * @Template("@PumukitWizard/Default/steps.html.twig")
     */
    public function stepsAction(Request $request): array
    {
        $step = $request->get('step');
        $option = $request->get('option');
        $showSeries = $request->get('show_series');
        $showLicense = $this->licenseService->isEnabled();
        $sameSeries = $request->get('same_series', false);

        return [
            'step' => $step,
            'option' => $option,
            'show_series' => $showSeries,
            'show_license' => $showLicense,
            'same_series' => $sameSeries,
        ];
    }

    private function getKeyData(string $key, array $formData, $default = [])
    {
        return array_key_exists($key, $formData) ? $formData[$key] : $default;
    }

    private function getSeries(array $seriesData = [])
    {
        $seriesId = $this->getKeyData('id', $seriesData);
        if ($seriesId && ('null' !== $seriesId)) {
            $series = $this->objectManager->getRepository(Series::class)->find($seriesId);
        } else {
            $series = $this->createSeries($seriesData);
        }

        return $series;
    }

    private function createSeries(array $seriesData = [])
    {
        if ($seriesData) {
            $series = $this->factoryService->createSeries($this->getUser());

            $i18nTitle = $this->getKeyData('i18n_title', $seriesData);
            if (empty(array_filter($i18nTitle))) {
                $seriesData = $this->getDefaultFieldValuesInData($seriesData, 'i18n_title', 'New', true);
            }

            $keys = ['i18n_title', 'i18n_subtitle', 'i18n_description'];

            return $this->setData($series, $seriesData, $keys);
        }
    }

    private function createMultimediaObject(array $mmData, Series $series)
    {
        $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $this->getUser());

        if ($mmData) {
            $keys = ['i18n_title', 'i18n_subtitle', 'i18n_description', 'i18n_line2'];
            $multimediaObject = $this->setData($multimediaObject, $mmData, $keys);
        }

        return $multimediaObject;
    }

    private function addTagToMultimediaObjectByCode(MultimediaObject $multimediaObject, $tagCode): array
    {
        $addedTags = [];

        if ($this->isGranted(Permission::getRoleTagDisableForPubChannel($tagCode))) {
            return $addedTags;
        }

        $tagRepo = $this->objectManager->getRepository(Tag::class);

        $tag = $tagRepo->findOneBy(['cod' => $tagCode]);
        if ($tag) {
            $addedTags = $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        }

        return $addedTags;
    }

    private function setData($resource, $resourceData, $keys)
    {
        foreach ($keys as $key) {
            $value = $this->getKeyData($key, $resourceData);
            $filterValue = array_filter($value);
            if (0 !== count($filterValue)) {
                $upperField = $this->getUpperFieldName($key);
                $setField = 'set'.$upperField;
                $resource->{$setField}($value);
            }
        }

        $this->objectManager->persist($resource);
        $this->objectManager->flush();

        return $resource;
    }

    /**
     * Get default field values in data for those important fields that can not be empty.
     */
    private function getDefaultFieldValuesInData(array $resourceData = [], string $fieldName = '', string $defaultValue = '', bool $isI18nField = false): array
    {
        if ($fieldName && $defaultValue) {
            if ($isI18nField) {
                $resourceData[$fieldName] = [];
                foreach ($this->locales as $locale) {
                    $resourceData[$fieldName][$locale] = $defaultValue;
                }
            } else {
                $resourceData[$fieldName] = $defaultValue;
            }
        }

        return $resourceData;
    }

    /**
     * Get uppercase field name Converts something like 'i18n_title' into 'I18nTitle'.
     */
    private function getUpperFieldName(string $key = ''): string
    {
        $pattern = '/_[a-z]?/';
        $aux = preg_replace_callback($pattern, static function ($matches) {
            return strtoupper(ltrim($matches[0], '_'));
        }, $key);

        return ucfirst($aux);
    }

    private function completeFormWithSeries(array $formData, Series $series): array
    {
        if (!$formData) {
            $formData = ['series' => [
                'i18n_title' => $series->getI18nTitle(),
                'i18n_subtitle' => $series->getI18nSubtitle(),
                'i18n_description' => $series->getI18nDescription(),
            ]];
        }
        if ($series->getId()) {
            $formData['series']['id'] = $series->getId();
        }

        return $formData;
    }

    private function getSameSeriesValue(array $formData = [], bool $sameSeriesFromRequest = false): bool
    {
        if (isset($formData['same_series'])) {
            return (bool) ($formData['same_series']);
        }

        return $sameSeriesFromRequest;
    }
}
