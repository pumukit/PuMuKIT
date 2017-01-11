<?php

namespace Pumukit\WizardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;

/**
 * @Security("is_granted('ROLE_ACCESS_WIZARD_UPLOAD')")
 */
class SimpleController extends Controller
{
    /**
     * @Template()
     */
    public function indexAction(Series $series, Request $request)
    {
        $licenseService = $this->get('pumukit_wizard.license');
        $licenseContent = $licenseService->getLicenseContent($request->getLocale());

        $languages = CustomLanguageType::getLanguageNames($this->container->getParameter('pumukit2.customlanguages'), $this->get('translator'));

        return array(
            'series' => $series,
            'languages' => $languages,
            'show_license' => $licenseService->isEnabled(),
            'license_text' => $licenseContent,
        );
    }

    public function uploadAction(Series $series, Request $request)
    {
        $jobService = $this->get('pumukitencoder.job');
        $inspectionService = $this->get('pumukit.inspection');

        $priority = 2;
        $profile = $this->get('pumukitencoder.profile')->getDefaultMasterProfile();
        $description = array();
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
                array(), $duration, JobService::ADD_JOB_NOT_CHECKS
            );
        } catch (\Exception $e) {
            //TODO Hanle error.
            throw $e;
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_shortener', array('id' => $multimediaObject->getId())));
    }

    /**
     * Create Multimedia Object.
     */
    private function createMultimediaObject($title, Series $series)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $multimediaObject = $factoryService->createMultimediaObject($series, true, $this->getUser());

        foreach ($this->container->getParameter('pumukit2.locales') as $locale) {
            $multimediaObject->setTitle($title, $locale);
        }

        return $multimediaObject;
    }
}
