<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Controller\InfoController;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversNothing]
class InfoControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private MultimediaObject $multimediaObject;
    private int $users = 0;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $series = new Series();
        $series->setNumericalID(1);
        $series->setTitle('Physics', 'en');
        $this->dm->persist($series);
        $this->multimediaObject = new MultimediaObject();
        $this->multimediaObject->setNumericalID(1);
        $this->multimediaObject->setSeries($series);
        $this->multimediaObject->setTitle('Waves', 'en');
        $this->dm->persist($this->multimediaObject);
        $this->dm->flush();
    }

    public function testEachBlockOfTheDashboardCanBeLoadedAlone(): void
    {
        $this->job(Job::STATUS_EXECUTING);
        $this->job(Job::STATUS_WAITING);
        $this->job(Job::STATUS_ERROR);
        $this->job(Job::STATUS_FINISHED);
        $this->login();

        $crawler = $this->client->request('GET', '/admin/encoder/');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('[data-controller="encoder--dashboard"][data-encoder--dashboard-busy-value="true"]'));
        $this->assertSame(['stats', 'pending', 'executing', 'executed', 'cpus'], $crawler->filter('[data-encoder--dashboard-target="block"]')->each(static fn (Crawler $node): string => (string) $node->attr('data-block')));
        $this->assertStringContainsString('Waves', $crawler->filter('[data-block="executing"]')->text());
        $this->assertCount(0, $crawler->filter('[data-controller="encoder--dashboard"] script, [data-controller="encoder--dashboard"] [onclick]'));

        $block = $this->client->request('GET', '/admin/encoder/block/executed?show_finished=1&show_error=0');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('X-Encoder-Busy', '1');
        $this->assertStringNotContainsString('<html', (string) $this->client->getResponse()->getContent());
        $this->assertCount(1, $block->filter('tbody tr'));
        $this->assertStringContainsString('Finished', $block->filter('tbody tr')->text());

        $this->client->request('GET', '/admin/encoder/block/unknown');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheBlocksEscapeTheStoredDataAndTheQuery(): void
    {
        $payload = '"><img src=x onerror=alert(1)>';
        $this->multimediaObject->setTitle($payload, 'en');
        for ($index = 0; $index < 6; ++$index) {
            $job = $this->job(Job::STATUS_ERROR);
            $job->setProfile($payload);
            $job->setCpu($payload);
        }
        $this->dm->flush();
        $this->login();

        foreach (InfoController::BLOCKS as $block) {
            $crawler = $this->client->request('GET', '/admin/encoder/block/'.$block, [$payload => $payload, 'nested' => [$payload => $payload]]);

            $this->assertResponseIsSuccessful();
            $this->assertStringNotContainsString('<img', (string) $this->client->getResponse()->getContent());
            $this->assertCount(0, $crawler->filter('img, script, [onerror]'));

            if ('executed' === $block) {
                $this->assertStringContainsString($payload, $crawler->filter('tbody')->text());
                $this->assertStringContainsString('onerror', (string) $crawler->filter('.pagination a[href]')->first()->attr('href'));
            }
        }
    }

    public function testTheActionsNeedTheTokenAndTheOwner(): void
    {
        $own = $this->job(Job::STATUS_WAITING, 'personal1@example.com');
        $other = $this->job(Job::STATUS_WAITING, 'other@example.com');
        $this->login(PermissionProfile::SCOPE_PERSONAL);

        $crawler = $this->client->request('GET', '/admin/encoder/job/'.$own->getId());
        $this->assertResponseIsSuccessful();
        $token = $crawler->filter('[data-encoder--job-token-value]')->attr('data-encoder--job-token-value');

        $this->client->request('POST', '/admin/encoder/job', ['jobId' => $own->getId(), 'priority' => 3]);
        $this->assertResponseStatusCodeSame(403);

        $this->client->request('POST', '/admin/encoder/job', ['jobId' => $other->getId(), 'priority' => 3, '_token' => $token]);
        $this->assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/admin/encoder/job/'.$other->getId());
        $this->assertResponseStatusCodeSame(403);

        $this->client->request('POST', '/admin/encoder/job', ['jobId' => $own->getId(), 'priority' => 9, '_token' => $token]);
        $this->assertResponseIsSuccessful();
        $this->dm->clear();
        $this->assertSame(3, $this->dm->getRepository(Job::class)->find($own->getId())->getPriority());

        $this->client->request('POST', '/admin/encoder/cpu/maintenance/activate/local', ['_token' => $token]);
        $this->assertResponseStatusCodeSame(403);
    }

    private function job(int $status, string $email = 'jobs@example.com'): Job
    {
        $job = new Job();
        $job->setMmId($this->multimediaObject->getId());
        $job->setProfile('master_copy');
        $job->setStatus($status);
        $job->setCpu('local');
        $job->setPathIni('/tmp/input.mp4');
        $job->setPathEnd('/tmp/output.mp4');
        $job->setEmail($email);
        $job->setPriority(1);
        $job->setTimeini(new \DateTime('-10 minutes'));
        if (Job::STATUS_WAITING !== $status) {
            $job->setTimestart(new \DateTime('-5 minutes'));
        }
        if (in_array($status, [Job::STATUS_ERROR, Job::STATUS_FINISHED], true)) {
            $job->setTimeend(new \DateTime('-1 minute'));
        }
        $this->dm->persist($job);
        $this->dm->flush();

        return $job;
    }

    private function login(string $scope = PermissionProfile::SCOPE_GLOBAL): void
    {
        ++$this->users;
        $user = new User();
        $user->setUsername('jobs'.$this->users);
        $user->setEmail(PermissionProfile::SCOPE_PERSONAL === $scope ? 'personal1@example.com' : 'jobs@example.com');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles([$scope, 'ROLE_ACCESS_JOBS']);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }
}
