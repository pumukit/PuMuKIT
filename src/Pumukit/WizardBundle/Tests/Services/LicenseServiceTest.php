<?php

namespace Pumukit\WizardBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\WizardBundle\Services\LicenseService;

class LicenseServiceTest extends WebTestCase
{
    private $translator;
    private $resourcesDir;
    private $licenseDir;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        $this->translator = static::$kernel->getContainer()->get('translator');
        $this->resourcesDir = realpath(__DIR__.'/../Resources');
        $this->licenseDir = realpath($this->resourcesDir.'/data/license');
    }

    public function tearDown()
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
        $this->assertFalse($licenseService->isEnabled());

        $showLicense = true;
        $locales = ['en'];
        $licenseService = new LicenseService($showLicense, $this->licenseDir, $locales, $this->translator);
        $this->assertTrue($licenseService->isEnabled());
    }

    public function testIsLicenseEnabledAndAccepted()
    {
        $showLicense = false;
        $locales = ['en'];
        $locale = 'en';
        $content = 'test';
        $licenseService = new LicenseService($showLicense, $this->licenseDir, $locales, $this->translator);
        $formData = [];
        $this->assertTrue($licenseService->isLicenseEnabledAndAccepted($formData, $locale));

        $showLicense = true;
        $licenseService = new LicenseService($showLicense, $this->licenseDir, $locales, $this->translator);
        $this->assertFalse($licenseService->isLicenseEnabledAndAccepted($formData, $locale));

        $formData = ['license' => ['accept' => true]];
        $this->assertTrue($licenseService->isLicenseEnabledAndAccepted($formData, $locale));

        $formData = ['license' => ['accept' => false]];
        $this->assertFalse($licenseService->isLicenseEnabledAndAccepted($formData, $locale));
    }

    public function testGetLicenseContent()
    {
        $showLicense = true;
        $locales = ['en'];
        $licenseService = new LicenseService($showLicense, $this->licenseDir, $locales, $this->translator);

        $licenseFile = realpath($this->licenseDir.'/en.txt');
        $licenseContent = @file_get_contents($licenseFile);
        $this->assertEquals($licenseContent, $licenseService->getLicenseContent());
        $this->assertEquals($licenseContent, $licenseService->getLicenseContent('en'));
    }
}
