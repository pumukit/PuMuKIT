<?php

namespace Pumukit\WizardBundle\Services;

use Symfony\Component\Translation\TranslatorInterface;

class LicenseService
{
    private $showLicense;
    private $licenseDir;
    private $locales;
    private $translator;

    /**
     * LicenseService constructor.
     *
     * @param $showLicense
     * @param $licenseDir
     * @param array               $locales
     * @param TranslatorInterface $translator
     *
     * @throws \Exception
     */
    public function __construct($showLicense, $licenseDir, array $locales, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->showLicense = $showLicense;
        $this->licenseDir = realpath($licenseDir);
        if ($this->showLicense && !$this->licenseDir) {
            throw new \Exception($this->translator->trans('Directory path not found: ').$licenseDir);
        }
        $this->locales = $locales;
        $this->checkLicenseFiles();
    }

    /**
     * Is license enabled to be shown on wizard steps.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->showLicense;
    }

    /**
     * Is license enabled and accepted.
     *
     * @param array      $formData
     * @param null|mixed $locale
     *
     * @return bool Returns FALSE if not enabled and not accepted, TRUE otherwise
     */
    public function isLicenseEnabledAndAccepted($formData = [], $locale = null)
    {
        if ($this->isEnabled()) {
            if (isset($formData['license']['accept']) && $formData['license']['accept']) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Get license content file.
     *
     * @param null|string $locale
     *
     * @throws \Exception
     *
     * @return bool|string
     */
    public function getLicenseContent($locale = null)
    {
        if (!$this->isEnabled()) {
            return '';
        }

        if ($locale) {
            $licenseFile = realpath($this->licenseDir.'/'.$locale.'.txt');
        } else {
            $licenseFile = $this->getAnyLicenseFile();
        }
        if (!$licenseFile) {
            throw new \Exception($this->translator->trans('Not valid locale "'.$locale.'". There is no license file in the directory "'.$this->licenseDir.'" in the format "{locale}.txt".'));
        }

        try {
            $licenseContent = file_get_contents($licenseFile);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        return $licenseContent;
    }

    /**
     * Check license files.
     *
     * Checks if there is any valid file in license directory.
     * Valid file names are {locale}.txt
     *
     * @throws \Exception
     *
     * @return bool
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
     * Get any license file path in locales.
     *
     * @return string $filepath
     */
    private function getAnyLicenseFile()
    {
        $licenseFile = false;
        foreach ($this->locales as $locale) {
            $licenseFile = realpath($this->licenseDir.'/'.$locale.'.txt');
            if ($licenseFile) {
                break;
            }
        }

        return $licenseFile;
    }
}
