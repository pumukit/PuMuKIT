<?php

namespace Pumukit\WizardBundle\Tests\Services;

use Pumukit\WizardBundle\Services\LicenseService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class LicenseServiceTest extends WebTestCase
{
    private $translator;
    private $resourcesDir;
    private $licenseDir;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        $this->translator = static::$kernel->getContainer()->get('translator');
        $this->resourcesDir = realpath(__DIR__.'/../Resources');
        $this->licenseDir = realpath($this->resourcesDir.'/data/license');
    }

    public function tearDown(): void
    {
        $this->translator = null;
        $this->resourcesDir = null;
        $this->licenseDir = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testIsEnabled()
    {
        $showLicense = false;
        $licenseDir = '';
        $locales = [];
        $licenseService = new LicenseService($showLicense, $licenseDir, $locales, $this->translator);
        static::assertFalse($licenseService->isEnabled());

        $showLicense = true;
        $locales = ['en'];
        $licenseService = new LicenseService($showLicense, $this->licenseDir, $locales, $this->translator);
        static::assertTrue($licenseService->isEnabled());
    }

    public function testIsLicenseEnabledAndAccepted()
    {
        $showLicense = false;
        $locales = ['en'];
        $locale = 'en';
        $content = 'test';
        $licenseService = new LicenseService($showLicense, $this->licenseDir, $locales, $this->translator);
        $formData = [];
        static::assertTrue($licenseService->isLicenseEnabledAndAccepted($formData, $locale));

        $showLicense = true;
        $licenseService = new LicenseService($showLicense, $this->licenseDir, $locales, $this->translator);
        static::assertFalse($licenseService->isLicenseEnabledAndAccepted($formData, $locale));

        $formData = ['license' => ['accept' => true]];
        static::assertTrue($licenseService->isLicenseEnabledAndAccepted($formData, $locale));

        $formData = ['license' => ['accept' => false]];
        static::assertFalse($licenseService->isLicenseEnabledAndAccepted($formData, $locale));
    }

    public function testGetLicenseContent()
    {
        $showLicense = true;
        $locales = ['en'];
        $licenseService = new LicenseService($showLicense, $this->licenseDir, $locales, $this->translator);

        $licenseFile = realpath($this->licenseDir.'/en.txt');
        $licenseContent = @file_get_contents($licenseFile);
        static::assertEquals($licenseContent, $licenseService->getLicenseContent());
        static::assertEquals($licenseContent, $licenseService->getLicenseContent('en'));
    }
}
