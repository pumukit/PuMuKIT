<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class JobOutputEscapingTest extends PumukitTestCase
{
    private const PAYLOAD = '<img src=x onerror=alert(1)>';

    private KernelBrowser $client;
    private MultimediaObject $multimediaObject;
    private Job $job;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $user = new User();
        $user->setUsername('jobs');
        $user->setEmail('jobs@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $this->dm->persist($user);

        $series = new Series();
        $series->setNumericalID(1);
        $this->dm->persist($series);

        $this->multimediaObject = new MultimediaObject();
        $this->multimediaObject->setNumericalID(1);
        $this->multimediaObject->setSeries($series);
        $this->dm->persist($this->multimediaObject);

        $this->job = new Job();
        $this->job->setMmId($this->multimediaObject->getId());
        $this->job->setProfile('master_copy');
        $this->job->setStatus(Job::STATUS_ERROR);
        $this->job->setPathIni('/tmp/'.self::PAYLOAD.'.mp4');
        $this->job->setPathEnd('/tmp/output.mp4');
        $this->job->setCpu('local');
        $this->job->setOutput("Input #0, mov,mp4, from '/tmp/x.mp4':\n    title           : ".self::PAYLOAD);
        $this->dm->persist($this->job);
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }

    #[DataProvider('jobPages')]
    public function testTheJobCommandAndOutputAreEscaped(string $page): void
    {
        $path = 'backoffice' === $page
            ? '/admin/track/job/'.$this->multimediaObject->getId().'/info/'.$this->job->getId()
            : '/admin/encoder/job/'.$this->job->getId();

        $this->client->request('GET', $path);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('onerror=alert(1)>', $content);
        $this->assertStringContainsString('onerror=alert(1)&gt;', $content);
        $this->assertStringContainsString('title&nbsp;&nbsp;', $content);
    }

    public static function jobPages(): iterable
    {
        yield 'backoffice' => ['backoffice'];

        yield 'encoder' => ['encoder'];
    }
}
