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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_WIZARD_UPLOAD')")
 */
class DefaultController extends AbstractController
{
    public const SERIES_LIMIT = 30;

    /**
     * @Template("@PumukitWizard/Default/license.html.twig")
     */
    public function licenseAction(Request $request, LicenseService $licenseService, bool $pumukitWizardShowTags, bool $pumukitWizardShowObjectLicense)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        if (!$licenseService->isEnabled()) {
            if ($sameSeries) {
                return $this->redirect($this->generateUrl('pumukitwizard_default_type', ['pumukitwizard_form_data' => $formData, 'id' => $formData['series']['id'], 'same_series' => $sameSeries]));
            }

            return $this->redirect($this->generateUrl('pumukitwizard_default_series', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        $licenseContent = $licenseService->getLicenseContent($request->getLocale());

        return [
            'license_text' => $licenseContent,
            'form_data' => $formData,
            'show_series' => $showSeries,
            'same_series' => $sameSeries,
            'license_enable' => $licenseService->isEnabled(),
            'show_tags' => $pumukitWizardShowTags,
            'show_object_license' => $pumukitWizardShowObjectLicense,
        ];
    }

    /**
     * @Template("@PumukitWizard/Default/series.html.twig")
     */
    public function seriesAction(Request $request, DocumentManager $documentManager, LicenseService $licenseService, bool $pumukitWizardShowTags, bool $pumukitWizardShowObjectLicense, bool $pumukitWizardMandatoryTitle, bool $pumukitWizardReuseSeries, bool $pumukitWizardReuseAdminSeries)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        $mandatoryTitle = $pumukitWizardMandatoryTitle ? 1 : 0;
        $userSeries = [];

        if ($pumukitWizardReuseSeries) {
            $user = $this->getUser();
            $reuseAdminSeries = $pumukitWizardReuseAdminSeries;
            $userSeries = $documentManager->getRepository(Series::class)->findUserSeries($user, $reuseAdminSeries);

            usort($userSeries, static function ($a, $b) use ($request) {
                return strcmp($a['_id']['title'][$request->getLocale()], $b['_id']['title'][$request->getLocale()]);
            });
        }

        return [
            'form_data' => $formData,
            'license_enable' => $licenseService->isEnabled(),
            'mandatory_title' => $mandatoryTitle,
            'reuse_series' => $pumukitWizardReuseSeries,
            'same_series' => $sameSeries,
            'user_series' => $userSeries,
            'show_tags' => $pumukitWizardShowTags,
            'show_object_license' => $pumukitWizardShowObjectLicense,
        ];
    }

    /**
     * @Template("@PumukitWizard/Default/type.html.twig")
     */
    public function typeAction(Request $request, DocumentManager $documentManager, LicenseService $licenseService, bool $pumukitWizardShowTags, bool $pumukitWizardShowObjectLicense, string $id)
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
        $series = $documentManager->getRepository(Series::class)->find($id);
        if ($series) {
            $formData = $this->completeFormWithSeries($formData, $series);
        }
        if (false === $this->get('security.authorization_checker')->isGranted(Permission::ACCESS_INBOX)) {
            $formData['series']['id'] = $id;
            $formData['type']['option'] = 'single';

            return $this->redirect($this->generateUrl('pumukitwizard_default_option', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['show_series' => $showSeries, 'pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        return [
            'series_id' => $id,
            'form_data' => $formData,
            'show_series' => $showSeries,
            'license_enable' => $licenseService->isEnabled(),
            'same_series' => $sameSeries,
            'show_tags' => $pumukitWizardShowTags,
            'show_object_license' => $pumukitWizardShowObjectLicense,
        ];
    }

    public function optionAction(Request $request, LicenseService $licenseService): RedirectResponse
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        if (('multiple' === $formData['type']['option']) && (false !== $this->get('security.authorization_checker')->isGranted(Permission::ACCESS_INBOX))) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_track', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        return $this->redirect($this->generateUrl('pumukitwizard_default_multimediaobject', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
    }

    /**
     * @Template("@PumukitWizard/Default/multimediaobject.html.twig")
     *
     * @param mixed $pumukitNewAdminLicenses
     * @param mixed $pumukitSchemaDefaultLicense
     * @param mixed $pumukitWizardTagParentCod
     */
    public function multimediaobjectAction(Request $request, DocumentManager $documentManager, LicenseService $licenseService, bool $pumukitWizardShowTags, $pumukitNewAdminLicenses, $pumukitSchemaDefaultLicense, bool $pumukitWizardShowObjectLicense, $pumukitWizardTagParentCod, bool $pumukitWizardMandatoryTitle)
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
            $series = $documentManager->getRepository(Series::class)->find($id);
            if ($series) {
                $formData = $this->completeFormWithSeries($formData, $series);
            }
        } elseif ($newSeries && isset($formData['series']['id'])) {
            $formData['series']['id'] = null;
            $formData['series']['reuse']['id'] = null;
        }
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        $availableTags = [];
        if ($pumukitWizardShowTags) {
            $tagParent = $documentManager->getRepository(Tag::class)->findOneBy(['cod' => $pumukitWizardTagParentCod]);
            if ($tagParent) {
                $availableTags = $tagParent->getChildren();
            }
        }
        $objectDefaultLicense = null;
        $objectAvailableLicenses = null;
        if ($pumukitWizardShowObjectLicense) {
            $objectDefaultLicense = $pumukitSchemaDefaultLicense;
            $objectAvailableLicenses = $pumukitNewAdminLicenses;
        }
        $mandatoryTitle = $pumukitWizardMandatoryTitle ? 1 : 0;

        return [
            'form_data' => $formData,
            'license_enable' => $licenseService->isEnabled(),
            'show_tags' => $pumukitWizardShowTags,
            'available_tags' => $availableTags,
            'show_object_license' => $pumukitWizardShowObjectLicense,
            'object_default_license' => $objectDefaultLicense,
            'object_available_licenses' => $objectAvailableLicenses,
            'mandatory_title' => $mandatoryTitle,
            'same_series' => $sameSeries,
            'show_series' => $showSeries,
        ];
    }

    /**
     * @Template("@PumukitWizard/Default/track.html.twig")
     */
    public function trackAction(Request $request, LicenseService $licenseService, ProfileService $profileService, FactoryService $factoryService, TranslatorInterface $translator, array $pumukitCustomLanguages, bool $pumukitWizardShowTags, bool $pumukitWizardShowObjectLicense)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        $masterProfiles = $profileService->getMasterProfiles(true);
        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);

        foreach ($pubChannelsTags as $key => $pubTag) {
            if ($pubTag->getProperty('hide_in_tag_group')) {
                unset($pubChannelsTags[$key]);
            }
        }

        $languages = CustomLanguageType::getLanguageNames($pumukitCustomLanguages, $translator);

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

        return [
            'form_data' => $formData,
            'status' => $status,
            'statusSelected' => $statusSelected,
            'master_profiles' => $masterProfiles,
            'pub_channels' => $pubChannelsTags,
            'languages' => $languages,
            'license_enable' => $licenseService->isEnabled(),
            'show_tags' => $pumukitWizardShowTags,
            'show_object_license' => $pumukitWizardShowObjectLicense,
            'same_series' => $sameSeries,
            'show_series' => $showSeries,
        ];
    }

    /**
     * @Template("@PumukitWizard/Default/upload.html.twig")
     */
    public function uploadAction(Request $request, DocumentManager $documentManager, TagService $tagService, FactoryService $factoryService, WizardService $wizardService, SortedMultimediaObjectsService $pumukitSchemaSortedMultimediaObjectService, LicenseService $licenseService, ProfileService $profileService, JobService $jobService, InspectionFfprobeService $inspectionFfprobeService, FormEventDispatcherService $formEventDispatcherService, bool $pumukitWizardShowTags, bool $pumukitWizardShowObjectLicense, array $locales)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, (bool) $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        $series = null;
        $seriesId = null;
        $multimediaObject = null;
        $mmId = null;

        if ($formData) {
            $seriesData = $this->getKeyData('series', $formData);

            $seriesId = $this->getKeyData('id', $seriesData);

            $typeData = $this->getKeyData('type', $formData);
            $trackData = $this->getKeyData('track', $formData);

            if (!$this->isGranted('ROLE_DISABLED_WIZARD_TRACK_PROFILES')) {
                $profile = $this->getKeyData('profile', $trackData, null);
            } else {
                $profile = $profileService->getDefaultMasterProfile();
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
                    if ('file' === $filetype) {
                        $resourceFile = $request->files->get('resource');
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
                        //exception if is not a mediafile (video or audio)
                        $duration = $inspectionFfprobeService->getDuration($filePath);
                    } catch (\Exception $e) {
                        throw new \Exception('The file is not a valid video or audio file');
                    }

                    if (0 == $duration) {
                        throw new \Exception('The file is not a valid video or audio file (duration is zero)');
                    }

                    $series = $this->getSeries($documentManager, $factoryService, $locales, $seriesData);
                    $multimediaObjectData = $this->getKeyData('multimediaobject', $formData);

                    $i18nTitle = $this->getKeyData('i18n_title', $multimediaObjectData);
                    if (empty(array_filter($i18nTitle))) {
                        $multimediaObjectData = $this->getDefaultFieldValuesInData($locales, $multimediaObjectData, 'i18n_title', 'New', true);
                    }

                    $multimediaObject = $this->createMultimediaObject($documentManager, $factoryService, $multimediaObjectData, $series);
                    $multimediaObject->setDuration($duration);

                    if ($pumukitWizardShowObjectLicense) {
                        $license = $this->getKeyData('license', $formData['multimediaobject']);
                        if ($license && ('0' !== $license)) {
                            $multimediaObject = $this->setData($documentManager, $multimediaObject, $formData['multimediaobject'], ['license']);
                        }
                    }

                    $formEventDispatcherService->dispatchSubmit($this->getUser(), $multimediaObject, $formData);

                    if ('file' === $filetype) {
                        $multimediaObject = $jobService->createTrackFromLocalHardDrive(
                            $multimediaObject,
                            $request->files->get('resource'),
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
                        $multimediaObject = $jobService->createTrackFromInboxOnServer(
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
                            $this->addTagToMultimediaObjectByCode($documentManager, $tagService, $multimediaObject, $tagCode);
                        }
                    }

                    if ($multimediaObject && isset($status)) {
                        $multimediaObject->setStatus((int) $status);
                    }

                    if ($pumukitWizardShowTags) {
                        $tagCode = $this->getKeyData('tag', $formData['multimediaobject']);
                        if ('0' !== $tagCode) {
                            $this->addTagToMultimediaObjectByCode($documentManager, $tagService, $multimediaObject, $tagCode);
                        }
                    }
                } elseif ('multiple' === $option) {
                    $this->denyAccessUnlessGranted(Permission::ACCESS_INBOX);

                    $series = $wizardService->uploadMultipleFiles(
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
                $documentManager->flush();
            } catch (\Exception $e) {
                $message = preg_replace("/\r|\n/", '', $e->getMessage());

                return [
                    'uploaded' => 'failed',
                    'message' => $message,
                    'option' => $option,
                    'seriesId' => $seriesId,
                    'mmId' => null,
                    'show_series' => $showSeries,
                    'same_series' => $sameSeries,
                ];
            }
        } else {
            return [
                'uploaded' => 'failed',
                'message' => 'No data received',
                'option' => null,
                'seriesId' => $seriesId,
                'mmId' => null,
                'show_series' => $showSeries,
                'same_series' => $sameSeries,
            ];
        }

        if ($series) {
            $seriesId = $series->getId();
            $pumukitSchemaSortedMultimediaObjectService->reorder($series);
        } else {
            $seriesId = null;
        }
        if ($multimediaObject) {
            $mmId = $multimediaObject->getId();
        } else {
            $mmId = null;
        }

        return [
            'uploaded' => 'success',
            'message' => 'Track(s) added',
            'option' => $option,
            'seriesId' => $seriesId,
            'mmId' => $mmId,
            'show_series' => $showSeries,
            'same_series' => $sameSeries,
        ];
    }

    /**
     * @Template("@PumukitWizard/Default/end.html.twig")
     */
    public function endAction(Request $request, DocumentManager $documentManager, LicenseService $licenseService): array
    {
        $series = $documentManager->getRepository(Series::class)->find($request->get('seriesId'));
        $multimediaObject = $documentManager->getRepository(MultimediaObject::class)->find($request->get('mmId'));
        $option = $request->get('option');
        $showSeries = $request->get('show_series');

        $licenseEnabled = $licenseService->isEnabled();

        $sameSeries = $request->get('same_series', false);

        return [
            'message' => 'success it seems',
            'series' => $series,
            'mm' => $multimediaObject,
            'option' => $option,
            'show_series' => $showSeries,
            'license_enabled' => $licenseEnabled,
            'same_series' => $sameSeries,
        ];
    }

    /**
     * @Template("@PumukitWizard/Default/error.html.twig")
     */
    public function errorAction(Request $request, DocumentManager $documentManager): array
    {
        $errorMessage = $request->get('errormessage');
        $option = $request->get('option');
        $showSeries = $request->get('show_series');

        $series = $documentManager->getRepository(Series::class)->find($request->get('seriesId'));
        $sameSeries = $request->get('same_series', false);

        return [
            'series' => $series,
            'message' => $errorMessage,
            'option' => $option,
            'show_series' => $showSeries,
            'same_series' => $sameSeries,
        ];
    }

    /**
     * @Template("@PumukitWizard/Default/steps.html.twig")
     */
    public function stepsAction(Request $request, LicenseService $licenseService): array
    {
        $step = $request->get('step');
        $option = $request->get('option');
        $showSeries = $request->get('show_series');
        $showLicense = $licenseService->isEnabled();
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

    private function getSeries(DocumentManager $documentManager, FactoryService $factoryService, array $locales, array $seriesData = [])
    {
        $seriesId = $this->getKeyData('id', $seriesData);
        if ($seriesId && ('null' !== $seriesId)) {
            $series = $documentManager->getRepository(Series::class)->find($seriesId);
        } else {
            $series = $this->createSeries($documentManager, $factoryService, $locales, $seriesData);
        }

        return $series;
    }

    private function createSeries(DocumentManager $documentManager, FactoryService $factoryService, array $locales, array $seriesData = [])
    {
        if ($seriesData) {
            $series = $factoryService->createSeries($this->getUser());

            $i18nTitle = $this->getKeyData('i18n_title', $seriesData);
            if (empty(array_filter($i18nTitle))) {
                $seriesData = $this->getDefaultFieldValuesInData($locales, $seriesData, 'i18n_title', 'New', true);
            }

            $keys = ['i18n_title', 'i18n_subtitle', 'i18n_description'];

            return $this->setData($documentManager, $series, $seriesData, $keys);
        }
    }

    private function createMultimediaObject(DocumentManager $documentManager, FactoryService $factoryService, array $mmData, Series $series)
    {
        $multimediaObject = $factoryService->createMultimediaObject($series, true, $this->getUser());

        if ($mmData) {
            $keys = ['i18n_title', 'i18n_subtitle', 'i18n_description', 'i18n_line2'];
            $multimediaObject = $this->setData($documentManager, $multimediaObject, $mmData, $keys);
        }

        return $multimediaObject;
    }

    private function addTagToMultimediaObjectByCode(DocumentManager $documentManager, TagService $tagService, MultimediaObject $multimediaObject, $tagCode): array
    {
        $addedTags = [];

        if ($this->isGranted(Permission::getRoleTagDisableForPubChannel($tagCode))) {
            return $addedTags;
        }

        $tagRepo = $documentManager->getRepository(Tag::class);

        $tag = $tagRepo->findOneBy(['cod' => $tagCode]);
        if ($tag) {
            $addedTags = $tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        }

        return $addedTags;
    }

    private function setData(DocumentManager $documentManager, $resource, $resourceData, $keys)
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

        $documentManager->persist($resource);
        $documentManager->flush();

        return $resource;
    }

    /**
     * Get default field values in data for those important fields that can not be empty.
     */
    private function getDefaultFieldValuesInData(array $locales, array $resourceData = [], string $fieldName = '', string $defaultValue = '', bool $isI18nField = false): array
    {
        if ($fieldName && $defaultValue) {
            if ($isI18nField) {
                $resourceData[$fieldName] = [];
                foreach ($locales as $locale) {
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
