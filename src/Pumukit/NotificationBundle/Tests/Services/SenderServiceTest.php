<?php

declare(strict_types=1);

namespace Pumukit\NotificationBundle\Tests\Services;

use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\NotificationBundle\Services\SenderService;
use Twig\Environment;

/**
 * @internal
 * @coversNothing
 */
class SenderServiceTest extends PumukitTestCase
{
    private $logger;
    private $senderService;
    private $mailer;
    private $templating;
    private $translator;
    private $enable;
    private $senderName;
    private $senderEmail;
    private $adminEmail;
    private $notificateErrorsToAdmin;
    private $platformName;
    private $environment;
    private $enableMultiLang;
    private $subjectSuccessTrans;
    private $locales;
    private $subjectFailsTrans;
    private $session;

    public function setUp(): void
    {
        $options = ['environment' => 'dev'];
        static::bootKernel($options);
        parent::setUp();
        $container = static::$kernel->getContainer();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();
        $this->templating = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $this->mailer = $container->get('mailer');
        $this->translator = $container->get('translator');
        $this->session = $container->get('session');
        $this->enable = true;
        $this->senderEmail = 'sender@pumukit.org';
        $this->senderName = 'Sender Pumukit';
        $this->enableMultiLang = true;
        $this->locales = ['en', 'es'];
        $this->subjectSuccessTrans = [0 => ['locale' => 'en', 'subject' => 'Job Success'], 1 => ['locale' => 'es', 'subject' => 'Trabajo exitoso']];
        $this->subjectFailsTrans = [0 => ['locale' => 'en', 'subject' => 'Job Fails'], 1 => ['locale' => 'es', 'subject' => 'Trabajo fallido']];
        $this->adminEmail = 'admin@pumukit.org';
        $this->notificateErrorsToAdmin = true;
        $this->platformName = 'Pumukit tv';
        $this->environment = 'dev';

        $this->senderService = new SenderService($this->mailer, $this->templating, $this->translator, $this->dm, $this->logger, $this->session, $this->enable, $this->senderEmail, $this->senderName, $this->enableMultiLang, $this->locales, $this->subjectSuccessTrans, $this->subjectFailsTrans, $this->adminEmail, $this->notificateErrorsToAdmin, $this->platformName);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->logger = null;
        $this->mailer = null;
        $this->templating = null;
        $this->translator = null;
        $this->enable = null;
        $this->senderEmail = null;
        $this->senderName = null;
        $this->adminEmail = null;
        $this->notificateErrorsToAdmin = null;
        $this->platformName = null;
        $this->senderService = null;
        gc_collect_cycles();
    }

    public function testIsEnabled(): void
    {
        static::assertEquals($this->enable, $this->senderService->isEnabled());
    }

    public function testGetSenderEmail(): void
    {
        static::assertEquals($this->senderEmail, $this->senderService->getSenderEmail());
    }

    public function testGetSenderName(): void
    {
        static::assertEquals($this->senderName, $this->senderService->getSenderName());
    }

    public function testGetAdminEmail(): void
    {
        static::assertEquals($this->adminEmail, $this->senderService->getAdminEmail());
    }

    public function testDoNotificationErrorsToAdmin(): void
    {
        static::assertEquals($this->notificateErrorsToAdmin, $this->senderService->doNotificationErrorsToAdmin());
    }

    public function testGetPlatformName(): void
    {
        static::assertEquals($this->platformName, $this->senderService->getPlatformName());
    }

    public function testSendNotification(): void
    {
        static::markTestSkipped('S');

        /*$mailTo = 'receiver@pumukit.org';
        $subject = 'Test sender service';
        $body = 'test send notification';
        $template = '@PumukitNotification/Email/notification.html.twig';
        $parameters = ['subject' => $subject, 'body' => $body, 'sender_name' => 'Sender Pumukit'];
        $output = $this->senderService->sendNotification($mailTo, $subject, $template, $parameters, false);
        static::assertEquals(1, $output);*/
    }
}
