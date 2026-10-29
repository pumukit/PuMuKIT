<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\EventListener;

use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
final class LegacyApiListenerTest extends PumukitTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $user = new User();
        $user->setUsername('legacy-api');
        $user->setEmail('legacy-api@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $this->dm->persist($user);

        $factory = self::getContainer()->get(FactoryService::class);
        $multimediaObject = $factory->createMultimediaObject($factory->createSeries());
        $multimediaObject->setKeywords(['video', 'lecture'], 'en');
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }

    public function testTheLegacyApiIsMarkedAsDeprecatedAndLogged(): void
    {
        $this->client->request('GET', '/api/keywords/mmobj.json', [], [], ['HTTP_USER_AGENT' => 'Integration/1.0', 'REMOTE_ADDR' => '203.0.113.42']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHasHeader('Deprecation');
        $this->assertSame('</api/v1/keywords/multimedia_objects>; rel="successor-version"', $this->client->getResponse()->headers->get('Link'));

        $handler = $this->client->getContainer()->get('monolog.handler.legacy_api');
        $this->assertTrue($handler->hasInfoThatPasses(static fn (array|LogRecord $record): bool => 'pumukit_schema_apikeywords_mmobj' === $record['context']['route']
            && 'legacy-api' === $record['context']['user']
            && '203.0.113.0' === $record['context']['ip']
            && 'Integration/1.0' === $record['context']['user_agent']));
    }

    public function testTheNewApiIsNotMarkedAndReturnsTheSameKeywords(): void
    {
        $this->client->request('GET', '/api/v1/keywords/multimedia_objects', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseNotHasHeader('Deprecation');
        $keywords = json_decode((string) $this->client->getResponse()->getContent(), true)['items'];
        $this->assertEqualsCanonicalizing(['lecture', 'video'], array_column($keywords, 'keyword'));

        $this->assertFalse($this->client->getContainer()->get('monolog.handler.legacy_api')->hasInfoRecords());
    }
}
