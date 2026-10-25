<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\ValueObject\Immutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class APIControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private User $user;
    private Series $series;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $this->user = new User();
        $this->user->setUsername('apiadmin');
        $this->user->setEmail('apiadmin@pumukit.local');
        $this->user->setPassword('x');
        $this->user->setEnabled(true);
        $this->user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ACCESS_API']);
        $this->dm->persist($this->user);
        $this->dm->flush();
        $this->series = new Series();
        $this->series->setNumericalID(1);
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->setSeries($this->series);
        $multimediaObject->setImmutable(Immutable::create(true, $this->user));
        $this->dm->persist($this->series);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();
        $this->dm->clear();
    }

    public function testImmutableKeepsTheUserIdAfterHydration(): void
    {
        $multimediaObject = $this->dm->getRepository(MultimediaObject::class)->findOneBy(['numerical_id' => 1]);

        $this->assertTrue($multimediaObject->getImmutable()->isBlocked());
        $this->assertSame($this->user->getId(), $multimediaObject->getImmutable()->user());
    }

    public function testMultimediaObjectsListsBlockedObjects(): void
    {
        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'apiadmin']);
        $this->client->loginUser($user, 'pumukit');

        $this->client->request('GET', '/api/media/mmobj.json', ['criteria' => ['series' => $this->series->getId()], 'sort' => -1]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['total']);
        $this->assertSame($this->user->getId(), $data['mmobjs'][0]['immutable']['user']);
    }
}
