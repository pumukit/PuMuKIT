<?php

namespace Pumukit\WizardBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\WizardBundle\Services\LicenseService;
use Symfony\Component\HttpFoundation\Response;

class LicenseServiceTest extends WebTestCase
{
    private $templating;
    private $translator;
    private $resourcesDir;
    private $licenseDir;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);
        $this->templating = static::$kernel->getContainer()->get('templating');
        $this->translator = static::$kernel->getContainer()->get('translator');
        $this->resourcesDir = realpath(__DIR__.'/../Resources');
        $this->licenseDir = realpath($this->resourcesDir . '/data/license');
    }

    public function tearDown()
    {
        $this->templating = null;
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
        $locales = array();
        $licenseService = new LicenseService($showLicense, $licenseDir, $locales, $this->templating, $this->translator);
        $this->assertFalse($licenseService->isEnabled());

        $showLicense = true;
        $locales = array('en');
        $licenseService = new LicenseService($showLicense, $this->licenseDir, $locales, $this->templating, $this->translator);
        $this->assertTrue($licenseService->isEnabled());
    }

    public function testIsLicenseEnabledAndAccepted()
    {
        $showLicense = false;
        $locales = array('en');
        $content = 'test';
        $templating = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $templating->expects($this->any())
            ->method('render')
            ->will($this->returnValue($content));
        $licenseService = new LicenseService($showLicense, $this->licenseDir, $locales, $templating, $this->translator);
        $formData = array();
        $this->assertFalse($licenseService->isLicenseEnabledAndAccepted($formData));
        
        $showLicense = true;
        $licenseService = new LicenseService($showLicense, $this->licenseDir, $locales, $templating, $this->translator);
        $response = $licenseService->isLicenseEnabledAndAccepted($formData);
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());
        
        $formData = array('license' => array('accept' => true));
        $this->assertTrue($licenseService->isLicenseEnabledAndAccepted($formData));
   
        $formData = array('license' => array('accept' => false));
        $response = $licenseService->isLicenseEnabledAndAccepted($formData);
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());
    }
}