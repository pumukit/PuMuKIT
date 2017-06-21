<?php

namespace Pumukit\WizardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;

/**
 * @Security("is_granted('ROLE_ACCESS_WIZARD_UPLOAD')")
 */
class DefaultController extends Controller
{
    const SERIES_LIMIT = 30;

    /**
     * @Template()
     */
    public function licenseAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', array());
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseService = $this->get('pumukit_wizard.license');
        if (!$licenseService->isEnabled()) {
            if ($sameSeries) {
                return $this->redirect($this->generateUrl('pumukitwizard_default_type', array('pumukitwizard_form_data' => $formData, 'id' => $formData['series']['id'], 'same_series' => $sameSeries)));
            }

            return $this->redirect($this->generateUrl('pumukitwizard_default_series', array('pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries)));
        }
        $licenseContent = $licenseService->getLicenseContent($request->getLocale());
        $showTags = $this->container->getParameter('pumukit_wizard.show_tags', false);
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license', false);

        return array(
            'license_text' => $licenseContent,
            'form_data' => $formData,
            'show_series' => $showSeries,
            'same_series' => $sameSeries,
            'license_enable' => $licenseService->isEnabled(),
            'show_tags' => $showTags,
            'show_object_license' => $showObjectLicense,
        );
    }

    /**
     * @Template()
     */
    public function seriesAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', array());
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', array('pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries)));
        }
        $mandatoryTitle = $this->getParameter('pumukit_wizard.mandatory_title') ? 1 : 0;
        $reuseSeries = $this->getParameter('pumukit_wizard.reuse_series');
        $userSeries = array();
        if ($reuseSeries) {
            $user = $this->getUser();
            $reuseAdminSeries = $this->getParameter('pumukit_wizard.reuse_admin_series');
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $userSeries = $dm->getRepository('PumukitSchemaBundle:Series')->findUserSeries($user, $reuseAdminSeries);
        }
        $showTags = $this->container->getParameter('pumukit_wizard.show_tags', false);
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license', false);

        return array(
                     'form_data' => $formData,
                     'license_enable' => $licenseService->isEnabled(),
                     'mandatory_title' => $mandatoryTitle,
                     'reuse_series' => $reuseSeries,
                     'same_series' => $sameSeries,
                     'user_series' => $userSeries,
                     'show_tags' => $showTags,
                     'show_object_license' => $showObjectLicense,
        );
    }

    /**
     * @Template()
     */
    public function typeAction($id, Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', array());
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
            if (!$id || ($id === 'null')) {
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

            return $this->redirect($this->generateUrl('pumukitwizard_default_option', array('pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries)));
        }
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', array('show_series' => $showSeries, 'pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries)));
        }
        $showTags = $this->container->getParameter('pumukit_wizard.show_tags', false);
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license', false);

        return array(
                     'series_id' => $id,
                     'form_data' => $formData,
                     'show_series' => $showSeries,
                     'license_enable' => $licenseService->isEnabled(),
                     'same_series' => $sameSeries,
                     'show_tags' => $showTags,
                     'show_object_license' => $showObjectLicense,
                    );
    }

    /**
     * Option action.
     */
    public function optionAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', array());
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', array('pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries)));
        }
        if (('multiple' == $formData['type']['option']) && (false !== $this->get('security.authorization_checker')->isGranted(Permission::ACCESS_INBOX))) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_track', array('pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries)));
        }

        return $this->redirect($this->generateUrl('pumukitwizard_default_multimediaobject', array('pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries)));
    }

    /**
     * @Template()
     */
    public function multimediaobjectAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', array());
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
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', array('pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries)));
        }

        $showTags = $this->container->getParameter('pumukit_wizard.show_tags', false);
        $availableTags = array();
        if ($showTags) {
            $tagCode = $this->container->getParameter('pumukit_wizard.tag_parent_code', '');
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $tagRepo = $dm->getRepository('PumukitSchemaBundle:Tag');
            $tagParent = $tagRepo->findOneBy(array('cod' => $tagCode));
            if ($tagParent) {
                $availableTags = $tagParent->getChildren();
            }
        }
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license', false);
        $objectDefaultLicense = null;
        $objectAvailableLicenses = null;
        if ($showObjectLicense) {
            $objectDefaultLicense = $this->container->getParameter('pumukitschema.default_license', null);
            $objectAvailableLicenses = $this->container->getParameter('pumukit_new_admin.licenses', null);
        }
        $mandatoryTitle = $this->getParameter('pumukit_wizard.mandatory_title') ? 1 : 0;

        return array(
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
        );
    }

    /**
     * @Template()
     */
    public function trackAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', array());
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', array('pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries)));
        }

        $masterProfiles = $this->get('pumukitencoder.profile')->getMasterProfiles(true);
        $factoryService = $this->get('pumukitschema.factory');
        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);

        $languages = CustomLanguageType::getLanguageNames($this->container->getParameter('pumukit2.customlanguages'), $this->get('translator'));

        $showTags = $this->container->getParameter('pumukit_wizard.show_tags', false);
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license', false);

        return array(
                     'form_data' => $formData,
                     'master_profiles' => $masterProfiles,
                     'pub_channels' => $pubChannelsTags,
                     'languages' => $languages,
                     'license_enable' => $licenseService->isEnabled(),
                     'show_tags' => $showTags,
                     'show_object_license' => $showObjectLicense,
                     'same_series' => $sameSeries,
                     'show_series' => $showSeries,
                     );
    }

    /**
     * Upload action.
     *
     * @Template()
     */
    public function uploadAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data', array());
        $sameSeries = $this->getSameSeriesValue($formData, $request->get('same_series', false));
        $showSeries = !$sameSeries;
        $formData['same_series'] = $sameSeries ? 1 : 0;
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabledAndAccepted = $licenseService->isLicenseEnabledAndAccepted($formData, $request->getLocale());
        if (!$licenseEnabledAndAccepted) {
            return $this->redirect($this->generateUrl('pumukitwizard_default_license', array('pumukitwizard_form_data' => $formData, 'same_series' => $sameSeries)));
        }
        $jobService = $this->get('pumukitencoder.job');
        $inspectionService = $this->get('pumukit.inspection');
        $showTags = $this->container->getParameter('pumukit_wizard.show_tags', false);
        $showObjectLicense = $this->container->getParameter('pumukit_wizard.show_object_license', false);

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

            if ($this->isGranted('ROLE_SCOPE_GLOBAL')) {
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

            //$showSeries = false;
            /* if (('null' === $seriesId) || (null === $seriesId)) { */
            /*     $showSeries = true; */
            /* } */

            // TODO Fragment this. Develop better way.
            $option = $this->getKeyData('option', $typeData);
            try {
                if ('single' === $option) {
                    $filetype = $this->getKeyData('filetype', $trackData);
                    if ('file' === $filetype) {
                        if (!$request->files->get('resource')->isValid()) {
                            throw new \Exception($request->files->get('resource')->getErrorMessage());
                        }
                        $filePath = $request->files->get('resource')->getPathname();
                    } elseif ('inbox' === $filetype) {
                        $filePath = $request->get('file');
                    } else {
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
                        if ($license && ($license !== '0')) {
                            $multimediaObject = $this->setData($multimediaObject, $formData['multimediaobject'], array('license'));
                        }
                    }

                    $formDispatcher->dispatchSubmit($this->getUser(), $multimediaObject, $formData);

                    if ('file' === $filetype) {
                        $selectedPath = $request->get('resource');
                        $multimediaObject = $jobService->createTrackFromLocalHardDrive($multimediaObject, $request->files->get('resource'), $profile, $priority, $language, $description,
                                                                                       array(), $duration, JobService::ADD_JOB_NOT_CHECKS);
                    } elseif ('inbox' === $filetype) {
                        $this->denyAccessUnlessGranted(Permission::ACCESS_INBOX);
                        $selectedPath = $request->get('file');
                        $multimediaObject = $jobService->createTrackFromInboxOnServer($multimediaObject, $request->get('file'), $profile, $priority, $language, $description,
                                                                                      array(), $duration, JobService::ADD_JOB_NOT_CHECKS);
                    }

                    if ($multimediaObject && $pubchannel) {
                        foreach ($pubchannel as $tagCode => $valueOn) {
                            $addedTags = $this->addTagToMultimediaObjectByCode($multimediaObject, $tagCode);
                        }
                    }

                    if ($showTags) {
                        $tagCode = $this->getKeyData('tag', $formData['multimediaobject']);
                        if ($tagCode != '0') {
                            $this->addTagToMultimediaObjectByCode($multimediaObject, $tagCode);
                        }
                    }
                } elseif ('multiple' === $option) {
                    $this->denyAccessUnlessGranted(Permission::ACCESS_INBOX);
                    $series = $this->getSeries($seriesData);
                    $selectedPath = $request->get('file');
                    $finder = new Finder();
                    $finder->files()->in($selectedPath);
                    foreach ($finder as $f) {
                        $filePath = $f->getRealpath();
                        try {
                            $duration = $inspectionService->getDuration($filePath);
                        } catch (\Exception $e) {
                            continue;
                        }
                        $titleData = $this->getDefaultFieldValuesInData(array(), 'i18n_title', $f->getRelativePathname(), true);
                        $multimediaObject = $this->createMultimediaObject($titleData, $series);
                        if ($multimediaObject) {
                            $formDispatcher->dispatchSubmit($this->getUser(), $multimediaObject, $formData);
                            try {
                                $multimediaObject = $jobService->createTrackFromInboxOnServer($multimediaObject, $filePath, $profile, $priority, $language, $description);
                            } catch (\Exception $e) {
                                // TODO: filter invalid files another way
                                if (!strpos($e->getMessage(), 'Unknown error')) {
                                    $this->removeInvalidMultimediaObject($multimediaObject, $series);
                                    throw $e;
                                }
                            }
                            foreach ($pubchannel as $tagCode => $valueOn) {
                                $addedTags = $this->addTagToMultimediaObjectByCode($multimediaObject, $tagCode);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // TODO filter unknown errors
                $message = preg_replace("/\r|\n/", '', $e->getMessage());

                return array(
                             'uploaded' => 'failed',
                             'message' => $message,
                             'option' => $option,
                             'seriesId' => $seriesId,
                             'mmId' => null,
                             'show_series' => $showSeries,
                             'same_series' => $sameSeries,
                             );
            }
        } else {
            // TODO THROW EXCEPTION OR RENDER SPECIFIC TEMPLATE WITH MESSAGE
            return array(
                         'uploaded' => 'failed',
                         'message' => 'No data received',
                         'option' => $option,
                         'seriesId' => $seriesId,
                         'mmId' => null,
                         'show_series' => $showSeries,
                         'same_series' => $sameSeries,
                         );
        }

        if ($series) {
            $seriesId = $series->getId();
        } else {
            $seriesId = null;
        }
        if ($multimediaObject) {
            $mmId = $multimediaObject->getId();
        } else {
            $mmId = null;
        }

        return array(
                     'uploaded' => 'success',
                     'message' => 'Track(s) added',
                     'option' => $option,
                     'seriesId' => $seriesId,
                     'mmId' => $mmId,
                     'show_series' => $showSeries,
                     'same_series' => $sameSeries,
                     );
    }

    /**
     * @Template()
     */
    public function endAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');

        $series = $this->findSeriesById($request->get('seriesId'));
        $multimediaObject = $mmRepo->find($request->get('mmId'));
        $option = $request->get('option');
        $showSeries = $request->get('show_series');

        $licenseService = $this->get('pumukit_wizard.license');
        $licenseEnabled = $licenseService->isEnabled();

        $sameSeries = $request->get('same_series', false);

        return array(
                     'message' => 'success it seems',
                     'series' => $series,
                     'mm' => $multimediaObject,
                     'option' => $option,
                     'show_series' => $showSeries,
                     'license_enabled' => $licenseEnabled,
                     'same_series' => $sameSeries,
                     );
    }

    /**
     * @Template()
     */
    public function errorAction(Request $request)
    {
        $errorMessage = $request->get('errormessage');
        $option = $request->get('option');
        $showSeries = $request->get('show_series');

        $series = $this->findSeriesById($request->get('seriesId'));
        $sameSeries = $request->get('same_series', false);

        return array(
                     'series' => $series,
                     'message' => $errorMessage,
                     'option' => $option,
                     'show_series' => $showSeries,
                     'same_series' => $sameSeries,
                     );
    }

    /**
     * @Template()
     */
    public function stepsAction(Request $request)
    {
        $step = $request->get('step');
        $option = $request->get('option');
        $showSeries = $request->get('show_series');
        $licenseService = $this->get('pumukit_wizard.license');
        $showLicense = $licenseService->isEnabled();
        $sameSeries = $request->get('same_series', false);

        return array(
                     'step' => $step,
                     'option' => $option,
                     'show_series' => $showSeries,
                     'show_license' => $showLicense,
                     'same_series' => $sameSeries,
                     );
    }

    /**
     * Get key data.
     */
    private function getKeyData($key, $formData, $default = array())
    {
        return array_key_exists($key, $formData) ? $formData[$key] : $default;
    }

    /**
     * Get series (new or existing one).
     */
    private function getSeries($seriesData = array())
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

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
     */
    private function createSeries($seriesData = array())
    {
        if ($seriesData) {
            $factoryService = $this->get('pumukitschema.factory');
            $series = $factoryService->createSeries($this->getUser());

            $i18nTitle = $this->getKeyData('i18n_title', $seriesData);
            if (empty(array_filter($i18nTitle))) {
                $seriesData = $this->getDefaultFieldValuesInData($seriesData, 'i18n_title', 'New', true);
            }

            $keys = array('i18n_title', 'i18n_subtitle', 'i18n_description');
            $series = $this->setData($series, $seriesData, $keys);

            return $series;
        }

        return;
    }

    /**
     * Create Multimedia Object.
     */
    private function createMultimediaObject($mmData, $series)
    {
        if ($series) {
            $factoryService = $this->get('pumukitschema.factory');
            $multimediaObject = $factoryService->createMultimediaObject($series, true, $this->getUser());

            if ($mmData) {
                $keys = array('i18n_title', 'i18n_subtitle', 'i18n_description', 'i18n_line2');
                $multimediaObject = $this->setData($multimediaObject, $mmData, $keys);
            }

            return $multimediaObject;
        }

        return;
    }

    /**
     * Add Tag to Multimedia Object by Code.
     */
    private function addTagToMultimediaObjectByCode(MultimediaObject $multimediaObject, $tagCode)
    {
        $addedTags = array();

        if ($this->isGranted(Permission::getRoleTagDisableForPubChannel($tagCode))) {
            return $addedTags;
        }

        $tagService = $this->get('pumukitschema.tag');
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $tagRepo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $tag = $tagRepo->findOneByCod($tagCode);
        if ($tag) {
            $addedTags = $tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        }

        return $addedTags;
    }

    /**
     * Set data.
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
     */
    private function removeInvalidMultimediaObject(MultimediaObject $multimediaObject, Series $series)
    {
        $series->removeMultimediaObject($multimediaObject);
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $dm->remove($multimediaObject);
        $dm->flush();
    }

    /**
     * Get default field values in data
     * for those important fields that can not be empty.
     */
    private function getDefaultFieldValuesInData($resourceData = array(), $fieldName = '', $defaultValue = '', $isI18nField = false)
    {
        if ($fieldName && $defaultValue) {
            if ($isI18nField) {
                $resourceData[$fieldName] = array();
                $locales = $this->container->getParameter('pumukit2.locales');
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
     */
    private function findSeriesById($id)
    {
        $seriesRepo = $this->get('doctrine_mongodb.odm.document_manager')
            ->getRepository('PumukitSchemaBundle:Series');

        return $seriesRepo->find($id);
    }

    /**
     * Complete Form with Series metadata.
     */
    private function completeFormWithSeries($formData, Series $series)
    {
        if (!$formData) {
            $formData = array('series' => array(
                'i18n_title' => $series->getI18nTitle(),
                'i18n_subtitle' => $series->getI18nSubtitle(),
                'i18n_description' => $series->getI18nDescription(),
            ));
        }
        if ($series->getId()) {
            $formData['series']['id'] = $series->getId();
        }

        return $formData;
    }

    /**
     * Get Same Series value.
     */
    private function getSameSeriesValue($formData = array(), $sameSeriesFromRequest = false)
    {
        if (isset($formData['same_series'])) {
            return (bool) ($formData['same_series']);
        } else {
            return (bool) $sameSeriesFromRequest;
        }
    }
}
