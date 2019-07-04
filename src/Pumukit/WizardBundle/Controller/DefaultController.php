<?php

namespace Pumukit\WizardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;
use Pumukit\SchemaBundle\Document\Tag;

/**
 * @Security("is_granted('ROLE_ACCESS_WIZARD_UPLOAD')")
 */
class DefaultController extends Controller
{
    const SERIES_LIMIT = 30;

    /**
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Template("PumukitWizardBundle:Default:license.html.twig")
     */
    public function licenseAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseService = $this->get('pumukit_wizard.license');
        if (!$licenseService->isEnabled()) {
            if ($sameSeries) {
                return $this->redirect($this->generateUrl('pumukitwizard_default_type', ['pumukitwizard_form_data' => $formData, 'id' => $formData['series']['id'], 'same_series' => $sameSeries]));
            }

            return $this->redirect($this->generateUrl('pumukitwizard_default_series', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        $licenseContent = $licenseService->getLicenseContent($request->getLocale());
        $showTags = $this->container->getParameter('pumukit_wizard.show_tags');
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license');

        return [
            'license_text' => $licenseContent,
            'form_data' => $formData,
            'show_series' => $showSeries,
            'same_series' => $sameSeries,
            'license_enable' => $licenseService->isEnabled(),
            'show_tags' => $showTags,
            'show_object_license' => $showObjectLicense,
        ];
    }

    /**
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Template("PumukitWizardBundle:Default:series.html.twig")
     */
    public function seriesAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        $mandatoryTitle = $this->getParameter('pumukit_wizard.mandatory_title') ? 1 : 0;
        $reuseSeries = $this->getParameter('pumukit_wizard.reuse_series');
        $userSeries = [];

        if ($reuseSeries) {
            $user = $this->getUser();
            $reuseAdminSeries = $this->getParameter('pumukit_wizard.reuse_admin_series');
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $userSeries = $dm->getRepository(Series::class)->findUserSeries($user, $reuseAdminSeries);

            usort($userSeries, function ($a, $b) use ($request) {
                return strcmp($a['_id']['title'][$request->getLocale()], $b['_id']['title'][$request->getLocale()]);
            });
        }
        $showTags = $this->container->getParameter('pumukit_wizard.show_tags');
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license');

        return [
            'form_data' => $formData,
            'license_enable' => $licenseService->isEnabled(),
            'mandatory_title' => $mandatoryTitle,
            'reuse_series' => $reuseSeries,
            'same_series' => $sameSeries,
            'user_series' => $userSeries,
            'show_tags' => $showTags,
            'show_object_license' => $showObjectLicense,
        ];
    }

    /**
     * @param         $id
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Template("PumukitWizardBundle:Default:type.html.twig")
     */
    public function typeAction($id, Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
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
        $series = $this->findSeriesById($id);
        if ($series) {
            $formData = $this->completeFormWithSeries($formData, $series);
        }
        if (false === $this->get('security.authorization_checker')->isGranted(Permission::ACCESS_INBOX)) {
            $formData['series']['id'] = $id;
            $formData['type']['option'] = 'single';

            return $this->redirect($this->generateUrl('pumukitwizard_default_option', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['show_series' => $showSeries, 'pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        $showTags = $this->container->getParameter('pumukit_wizard.show_tags');
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license');

        return [
            'series_id' => $id,
            'form_data' => $formData,
            'show_series' => $showSeries,
            'license_enable' => $licenseService->isEnabled(),
            'same_series' => $sameSeries,
            'show_tags' => $showTags,
            'show_object_license' => $showObjectLicense,
        ];
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * Option action
     */
    public function optionAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        if (('multiple' == $formData['type']['option']) && (false !== $this->get('security.authorization_checker')->isGranted(Permission::ACCESS_INBOX))) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_track', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        return $this->redirect($this->generateUrl('pumukitwizard_default_multimediaobject', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     * @Template("PumukitWizardBundle:Default:multimediaobject.html.twig")
     */
    public function multimediaobjectAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        if (!isset($formData['series']['new'])) {
            $formData['series']['new'] = 0;
        }
        $newSeries = (bool) $formData['series']['new'];
        if (!$newSeries && isset($formData['series']['reuse']['id'])) {
            $id = $formData['series']['reuse']['id'];
            $formData['series']['id'] = $id;
            $series = $this->findSeriesById($id);
            if ($series) {
                $formData = $this->completeFormWithSeries($formData, $series);
            }
        } elseif ($newSeries && isset($formData['series']['id'])) {
            $id = null;
            $formData['series']['id'] = null;
            $formData['series']['reuse']['id'] = null;
            $series = $this->findSeriesById($id);
            if ($series) {
                $formData = $this->completeFormWithSeries($formData, $series);
            }
        }
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        $showTags = $this->container->getParameter('pumukit_wizard.show_tags');
        $availableTags = [];
        if ($showTags) {
            $tagCode = $this->container->getParameter('pumukit_wizard.tag_parent_code');
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $tagRepo = $dm->getRepository(Tag::class);
            $tagParent = $tagRepo->findOneBy(['cod' => $tagCode]);
            if ($tagParent) {
                $availableTags = $tagParent->getChildren();
            }
        }
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license');
        $objectDefaultLicense = null;
        $objectAvailableLicenses = null;
        if ($showObjectLicense) {
            $objectDefaultLicense = $this->container->getParameter('pumukitschema.default_license');
            $objectAvailableLicenses = $this->container->getParameter('pumukit_new_admin.licenses');
        }
        $mandatoryTitle = $this->getParameter('pumukit_wizard.mandatory_title') ? 1 : 0;

        return [
            'form_data' => $formData,
            'license_enable' => $licenseService->isEnabled(),
            'show_tags' => $showTags,
            'available_tags' => $availableTags,
            'show_object_license' => $showObjectLicense,
            'object_default_license' => $objectDefaultLicense,
            'object_available_licenses' => $objectAvailableLicenses,
            'mandatory_title' => $mandatoryTitle,
            'same_series' => $sameSeries,
            'show_series' => $showSeries,
        ];
    }

    /**
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Template("PumukitWizardBundle:Default:track.html.twig")
     */
    public function trackAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }

        $masterProfiles = $this->get('pumukitencoder.profile')->getMasterProfiles(true);
        $factoryService = $this->get('pumukitschema.factory');
        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);

        foreach ($pubChannelsTags as $key => $pubTag) {
            if ($pubTag->getProperty('hide_in_tag_group')) {
                unset($pubChannelsTags[$key]);
            }
        }

        $languages = CustomLanguageType::getLanguageNames($this->container->getParameter('pumukit.customlanguages'), $this->get('translator'));

        $showTags = $this->container->getParameter('pumukit_wizard.show_tags');
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license');

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
            'show_tags' => $showTags,
            'show_object_license' => $showObjectLicense,
            'same_series' => $sameSeries,
            'show_series' => $showSeries,
        ];
    }

    /**
     * Upload action.
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Exception
     *
     * @Template("PumukitWizardBundle:Default:upload.html.twig")
     */
    public function uploadAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $formData = $request->get('pumukitwizard_form_data', []);
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', ['pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries]));
        }
        $jobService = $this->get('pumukitencoder.job');
        $inspectionService = $this->get('pumukit.inspection');
        $showTags = $this->container->getParameter('pumukit_wizard.show_tags');
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license');

        $series = null;
        $seriesId = null;
        $multimediaObject = null;
        $mmId = null;

        $formDispatcher = $this->get('pumukit_wizard.form_dispatcher');

        if ($formData) {
            $seriesData = $this->getKeyData('series', $formData);

            $seriesId = $this->getKeyData('id', $seriesData);

            $typeData = $this->getKeyData('type', $formData);
            $trackData = $this->getKeyData('track', $formData);

            if (!$this->isGranted('ROLE_DISABLED_WIZARD_TRACK_PROFILES')) {
                $profile = $this->getKeyData('profile', $trackData, null);
            } else {
                $profile = $this->get('pumukitencoder.profile')->getDefaultMasterProfile();
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

            //$showSeries = false;
            /* if (('null' === $seriesId) || (null === $seriesId)) { */
            /*     $showSeries = true; */
            /* } */

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
                        $duration = $inspectionService->getDuration($filePath);
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

                    if ($showObjectLicense) {
                        $license = $this->getKeyData('license', $formData['multimediaobject']);
                        if ($license && ('0' !== $license)) {
                            $multimediaObject = $this->setData($multimediaObject, $formData['multimediaobject'], ['license']);
                        }
                    }

                    $formDispatcher->dispatchSubmit($this->getUser(), $multimediaObject, $formData);

                    if ('file' === $filetype) {
                        $selectedPath = $request->get('resource');
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
                        $selectedPath = $request->get('file');
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
                            $this->addTagToMultimediaObjectByCode($multimediaObject, $tagCode);
                        }
                    }

                    if ($multimediaObject && isset($status)) {
                        $multimediaObject->setStatus((int) $status);
                    }

                    if ($showTags) {
                        $tagCode = $this->getKeyData('tag', $formData['multimediaobject']);
                        if ('0' != $tagCode) {
                            $this->addTagToMultimediaObjectByCode($multimediaObject, $tagCode);
                        }
                    }
                } elseif ('multiple' === $option) {
                    $this->denyAccessUnlessGranted(Permission::ACCESS_INBOX);

                    $wizardService = $this->get('pumukit_wizard.wizard');

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
                $dm->flush();
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
            $this->get('pumukitschema.sorted_multimedia_object')->reorder($series);
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
     * @param Request $request
     *
     * @return array
     *
     * @Template("PumukitWizardBundle:Default:end.html.twig")
     */
    public function endAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmRepo = $dm->getRepository(MultimediaObject::class);

        $series = $this->findSeriesById($request->get('seriesId'));
        $multimediaObject = $mmRepo->find($request->get('mmId'));
        $option = $request->get('option');
        $showSeries = $request->get('show_series');

        $licenseService = $this->get('pumukit_wizard.license');
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
     * @param Request $request
     *
     * @return array
     *
     * @Template("PumukitWizardBundle:Default:error.html.twig")
     */
    public function errorAction(Request $request)
    {
        $errorMessage = $request->get('errormessage');
        $option = $request->get('option');
        $showSeries = $request->get('show_series');

        $series = $this->findSeriesById($request->get('seriesId'));
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
     * @param Request $request
     *
     * @return array
     *
     * @Template("PumukitWizardBundle:Default:steps.html.twig")
     */
    public function stepsAction(Request $request)
    {
        $step = $request->get('step');
        $option = $request->get('option');
        $showSeries = $request->get('show_series');
        $licenseService = $this->get('pumukit_wizard.license');
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

    /**
     * Get key data.
     *
     * @param       $key
     * @param       $formData
     * @param array $default
     *
     * @return array
     */
    private function getKeyData($key, $formData, $default = [])
    {
        return array_key_exists($key, $formData) ? $formData[$key] : $default;
    }

    /**
     * Get series (new or existing one).
     *
     * @param array $seriesData
     *
     * @return mixed|Series|void
     */
    private function getSeries($seriesData = [])
    {
        $seriesId = $this->getKeyData('id', $seriesData);
        if ($seriesId && ('null' !== $seriesId)) {
            $series = $this->findSeriesById($seriesId);
        } else {
            $series = $this->createSeries($seriesData);
        }

        return $series;
    }

    /**
     * Create Series.
     *
     * @param array $seriesData
     *
     * @return mixed|Series|void
     */
    private function createSeries($seriesData = [])
    {
        if ($seriesData) {
            $factoryService = $this->get('pumukitschema.factory');
            $series = $factoryService->createSeries($this->getUser());

            $i18nTitle = $this->getKeyData('i18n_title', $seriesData);
            if (empty(array_filter($i18nTitle))) {
                $seriesData = $this->getDefaultFieldValuesInData($seriesData, 'i18n_title', 'New', true);
            }

            $keys = ['i18n_title', 'i18n_subtitle', 'i18n_description'];
            $series = $this->setData($series, $seriesData, $keys);

            return $series;
        }

        return;
    }

    /**
     * Create Multimedia Object.
     *
     * @param $mmData
     * @param $series
     *
     * @return mixed|MultimediaObject|void
     */
    private function createMultimediaObject($mmData, $series)
    {
        if ($series) {
            $factoryService = $this->get('pumukitschema.factory');
            $multimediaObject = $factoryService->createMultimediaObject($series, true, $this->getUser());

            if ($mmData) {
                $keys = ['i18n_title', 'i18n_subtitle', 'i18n_description', 'i18n_line2'];
                $multimediaObject = $this->setData($multimediaObject, $mmData, $keys);
            }

            return $multimediaObject;
        }

        return;
    }

    /**
     * Add Tag to Multimedia Object by Code.
     *
     * @param MultimediaObject $multimediaObject
     * @param                  $tagCode
     *
     * @return array
     */
    private function addTagToMultimediaObjectByCode(MultimediaObject $multimediaObject, $tagCode)
    {
        $addedTags = [];

        if ($this->isGranted(Permission::getRoleTagDisableForPubChannel($tagCode))) {
            return $addedTags;
        }

        $tagService = $this->get('pumukitschema.tag');
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $tagRepo = $dm->getRepository(Tag::class);

        $tag = $tagRepo->findOneByCod($tagCode);
        if ($tag) {
            $addedTags = $tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        }

        return $addedTags;
    }

    /**
     * Set data.
     *
     * @param $resource
     * @param $resourceData
     * @param $keys
     *
     * @return mixed
     */
    private function setData($resource, $resourceData, $keys)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        foreach ($keys as $key) {
            $value = $this->getKeyData($key, $resourceData);
            if ($value) {
                $upperField = $this->getUpperFieldName($key);
                $setField = 'set'.$upperField;
                $resource->$setField($value);
            }
        }

        $dm->persist($resource);
        $dm->flush();

        return $resource;
    }

    /**
     * Remove Invalid Multimedia Object.
     *
     * @param MultimediaObject $multimediaObject
     * @param Series           $series
     */
    private function removeInvalidMultimediaObject(MultimediaObject $multimediaObject, Series $series)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $dm->remove($multimediaObject);
        $dm->flush();
    }

    /**
     * Get default field values in data
     * for those important fields that can not be empty.
     *
     * @param array  $resourceData
     * @param string $fieldName
     * @param string $defaultValue
     * @param bool   $isI18nField
     *
     * @return array
     */
    private function getDefaultFieldValuesInData($resourceData = [], $fieldName = '', $defaultValue = '', $isI18nField = false)
    {
        if ($fieldName && $defaultValue) {
            if ($isI18nField) {
                $resourceData[$fieldName] = [];
                $locales = $this->container->getParameter('pumukit.locales');
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
     * Get uppercase field name
     * Converts something like 'i18n_title' into 'I18nTitle'.
     *
     * @param string $key
     *
     * @return string
     */
    private function getUpperFieldName($key = '')
    {
        $pattern = '/_[a-z]?/';
        $aux = preg_replace_callback($pattern, function ($matches) {
            return strtoupper(ltrim($matches[0], '_'));
        }, $key);

        return ucfirst($aux);
    }

    /**
     * Find Series in Repository.
     *
     * @param $id
     *
     * @return mixed
     */
    private function findSeriesById($id)
    {
        $seriesRepo = $this->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(Series::class);

        return $seriesRepo->find($id);
    }

    /**
     * Complete Form with Series metadata.
     *
     * @param        $formData
     * @param Series $series
     *
     * @return array
     */
    private function completeFormWithSeries($formData, Series $series)
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

    /**
     * Get Same Series value.
     *
     * @param array $formData
     * @param bool  $sameSeriesFromRequest
     *
     * @return bool
     */
    private function getSameSeriesValue($formData = [], $sameSeriesFromRequest = false)
    {
        if (isset($formData['same_series'])) {
            return (bool) ($formData['same_series']);
        } else {
            return (bool) $sameSeriesFromRequest;
        }
    }
}
