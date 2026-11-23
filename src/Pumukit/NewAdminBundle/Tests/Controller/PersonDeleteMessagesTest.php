<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class PersonDeleteMessagesTest extends PumukitTestCase
{
    private const NAME = 'Ana Pérez';

    private KernelBrowser $client;
    private Person $person;

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

        $this->person = new Person();
        $this->person->setName(self::NAME);
        $this->dm->persist($this->person);
        $this->dm->flush();

        $this->client->loginUser($admin, 'pumukit');
    }

    public function testDeletingAPersonWithMultimediaObjectsNamesThePerson(): void
    {
        $this->linkToAMultimediaObject();

        $this->client->request('DELETE', $this->url('pumukitnewadmin_person_delete', ['id' => $this->person->getId()]));

        $this->assertResponseStatusCodeSame(409);
        $this->assertMessage('Can not delete the person "%name%": it has multimedia objects.');
    }

    public function testBatchDeletingAPersonWithMultimediaObjectsNamesThePerson(): void
    {
        $this->linkToAMultimediaObject();

        $this->client->request('DELETE', $this->url('pumukitnewadmin_person_batch_delete', ['ids' => json_encode([$this->person->getId()])]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertMessage('Can not delete the person "%name%": it has multimedia objects.');
    }

    public function testDeletingThePersonOfAUserNamesThePerson(): void
    {
        $user = new User();
        $user->setUsername('ana');
        $user->setEmail('ana@pumukit.local');
        $user->setPassword('unused');
        $user->setPerson($this->person);
        $this->person->setUser($user);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->client->request('DELETE', $this->url('pumukitnewadmin_person_delete', ['id' => $this->person->getId()]));

        $this->assertResponseStatusCodeSame(409);
        $this->assertMessage('Can not delete the person "%name%".');
    }

    private function linkToAMultimediaObject(): void
    {
        $role = new Role();
        $role->setCod('actor');
        $this->dm->persist($role);

        $series = new Series();
        $series->setNumericalID(1);
        $this->dm->persist($series);

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setSeries($series);
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->addPersonWithRole($this->person, $role);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();
    }

    private function assertMessage(string $key): void
    {
        $content = (string) $this->client->getResponse()->getContent();

        $this->assertSame(static::getContainer()->get('translator')->trans($key, ['%name%' => self::NAME]), $content);
        $this->assertStringContainsString('"'.self::NAME.'"', $content);
    }

    private function url(string $route, array $parameters = []): string
    {
        return static::getContainer()->get('router')->generate($route, $parameters);
    }
}
