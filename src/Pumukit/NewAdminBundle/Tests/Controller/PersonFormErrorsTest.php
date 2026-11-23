<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class PersonFormErrorsTest extends PumukitTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $admin = new User();
        $admin->setUsername('people');
        $admin->setEmail('people@pumukit.local');
        $admin->setPassword('unused');
        $admin->setEnabled(true);
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $this->dm->persist($admin);
        $this->dm->flush();

        $this->client->loginUser($admin, 'pumukit');
    }

    public function testCreatingAPersonWithAnInvalidEmailExplainsTheError(): void
    {
        $this->client->request('POST', '/admin/person/create', $this->formWithAnInvalidEmail());

        $this->assertResponseStatusCodeSame(409);
        $this->assertStringContainsString('email value not-an-email', (string) $this->client->getResponse()->getContent());
    }

    public function testUpdatingAPersonWithAnInvalidEmailExplainsTheError(): void
    {
        $person = new Person();
        $person->setName('Ana');
        $this->dm->persist($person);
        $this->dm->flush();

        $this->client->request('POST', '/admin/person/'.$person->getId().'/update', $this->formWithAnInvalidEmail());

        $this->assertResponseStatusCodeSame(409);
        $this->assertStringContainsString('email value not-an-email', (string) $this->client->getResponse()->getContent());
    }

    private function formWithAnInvalidEmail(): array
    {
        $i18n = ['en' => ''];

        return ['pumukitnewadmin_person' => [
            'i18n_honorific' => $i18n,
            'name' => 'Ana',
            'i18n_post' => $i18n,
            'i18n_firm' => $i18n,
            'i18n_bio' => $i18n,
            'email' => 'not-an-email',
        ]];
    }
}
