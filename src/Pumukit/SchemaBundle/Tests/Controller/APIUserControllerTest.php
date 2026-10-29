<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\User;

/**
 * @internal
 */
#[CoversNothing]
class APIUserControllerTest extends PumukitTestCase
{
    public function testTheUsersAreListedWithoutTheirCredentials(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $user = new User();
        $user->setUsername('api-user');
        $user->setEmail('api-user@pumukit.local');
        $user->setPassword('stored-password-hash');
        $user->setSalt('stored-salt');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ACCESS_API']);
        $person = new Person();
        $person->setName('Api Person');
        $person->setUser($user);
        $user->setPerson($person);
        $this->dm->persist($person);
        $this->dm->persist($user);
        $this->dm->flush();

        $client->loginUser($user, 'pumukit');
        $client->request('GET', '/api/user/users.json');

        $this->assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('api-user', $content);
        $this->assertStringContainsString('Api Person', $content);
        $this->assertStringNotContainsString('stored-password-hash', $content);
        $this->assertStringNotContainsString('stored-salt', $content);
    }
}
