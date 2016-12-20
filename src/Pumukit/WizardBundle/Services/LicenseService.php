<?php

namespace Pumukit\WizardBundle\Services;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Response;

class LicenseService
{
    private $showLicense;
    private $licenseDir;
    private $locales;
    private $templating;
    private $translator;

    /**
     * Constructor
     *
     * @param boolean             $showLicense
     * @param string              $licenseDir
     * @param array               $locales
     * @param EngineInterface     $templating
     * @param TranslatorInterface $translator
     */
    public function __construct($showLicense = false, $licenseDir = '', array $locales = array(), EngineInterface $templating, TranslatorInterface $translator)
    {
        $this->showLicense = $showLicense;
        $this->licenseDir = realpath($licenseDir);
        if ($this->showLicense && !$this->licenseDir) {
            throw new \Exception($this->translator->trans('Directory path not found: ') . $licenseDir);
        }
        $this->locales = $locales;
        $this->templating = $templating;
        $this->translator = $translator;
        $this->checkLicenseFiles();
    }

    /**
     * Is license enabled to be shown on wizard steps
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->showLicense;
    }

    
    /**
     * Is license enabled and accepted
     *
     * @param  array            $formData
     * @return boolean|Response
     */
    public function isLicenseEnabledAndAccepted($formData = array(), $locale = null)
    {
        if ($this->isEnabled()) {
	        if (array_key_exists('license', $formData)) {
                if (array_key_exists('accept', $formData['license'])) {
	                if ($formData['license']['accept']) {
                        return true;
                    } else {
                        return $this->renderLicenseNotAccepted($formData, $locale);
		            }
                }
            }

            return $this->renderLicenseNotAccepted($formData, $locale);
        }

        return false;
    }

    /**
     * Get license content file
     *
     * @param  string $locale
     * @return string $licenseContent
     */
    public function getLicenseContent($locale = null)
    {
        if ($locale) {
            $licenseFile = realpath($this->licenseDir . '/' . $locale . '.txt');
        } else {
            $licenseFile = $this->getAnyLicenseFile();
        }
        if (!$licenseFile) {
            throw new \Exception($this->translator->trans('Not valid locale "'.$locale.'". There is no license file in the directory "'.$this->licenseDir.'" in the format "{locale}.txt".'));
        }
        try {
            $licenseContent = @file_get_contents($licenseFile);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        return $licenseContent;
    }

    /**
     * Check license files
     *
     * Checks if there is any valid file in license directory.
     * Valid file names are {locale}.txt
     *
     * @return boolean|Exception true if there is any valid file, throws Exception otherwise
     */
    private function checkLicenseFiles()
    {
        if (!$this->showLicense) {
            return true;
        }
        $licenseFile = $this->getAnyLicenseFile();
        if (!$licenseFile) {
            throw new \Exception($this->translator->trans('Showing License is enabled but there is no valid license file in License Directory "'.$this->licenseDir.'" in the format "{locale}.txt".'));
        }

        return true;
    }

    /**
     * Get any license file path in locales
     *
     * @return string $filepath
     */
    private function getAnyLicenseFile()
    {
        $licenseFile = false;
        foreach ($this->locales as $locale) {
            $licenseFile = realpath($this->licenseDir . '/' . $locale . '.txt');
            if ($licenseFile) {
                break;
            }
        }

        return $licenseFile;
    }

    /**
     * Render license not accepted
     *
     * @param  array    $formData
     * @param  string   $locale
     * @return Response
     */
    private function renderLicenseNotAccepted($formData = array(), $locale = null)
    {
        $licenseContent = $this->getLicenseContent($locale);
        $renderedView = $this->templating->render('PumukitWizardBundle:Default:license.html.twig', array('show_error' => true, 'license_text' => $licenseContent, 'form_data' => $formData));

        return new Response($renderedView, Response::HTTP_FORBIDDEN);
    }
}