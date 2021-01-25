<?php

declare(strict_types=1);

namespace Pumukit\WizardBundle\Services;

use Symfony\Contracts\Translation\TranslatorInterface;

class LicenseService
{
    private $showLicense;
    private $licenseDir;
    private $locales;
    private $translator;

    public function __construct(bool $showLicense, string $licenseDir, array $locales, TranslatorInterface $translator)
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

    public function isEnabled(): bool
    {
        return $this->showLicense;
    }

    public function isLicenseEnabledAndAccepted($formData = [], $locale = null): bool
    {
        if ($this->isEnabled()) {
            return isset($formData['license']['accept']) && $formData['license']['accept'];
        }

        return true;
    }

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
     * Checks if there is any valid file in license directory.  Valid file names are {locale}.txt.
     */
    private function checkLicenseFiles(): bool
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
