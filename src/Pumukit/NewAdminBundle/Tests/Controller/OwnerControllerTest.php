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
class OwnerControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private string $url;
    private string $multimediaObjectId;
    private string $coOwnerId;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $role = new Role();
        $role->setCod('owner');
        $this->dm->persist($role);

        $owner = $this->user('owner');
        $coOwner = $this->user('coowner');

        $series = new Series();
        $series->setNumericalID(1);
        $this->dm->persist($series);

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setSeries($series);
        $multimediaObject->setProperty('owners', [$owner->getId(), $coOwner->getId()]);
        $multimediaObject->addPersonWithRole($coOwner->getPerson(), $role);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->multimediaObjectId = $multimediaObject->getId();
        $this->coOwnerId = $coOwner->getId();
        $this->url = '/admin/reject/'.$multimediaObject->getId().'/owner/'.$owner->getId().'/coowner/'.$coOwner->getId();
        $this->client->loginUser($owner, 'pumukit');
    }

    public function testTheEmailLinkAsksForConfirmationWithoutRejecting(): void
    {
        $crawler = $this->client->request('GET', $this->url);

        $this->assertResponseIsSuccessful();
        $this->assertSame('post', strtolower($crawler->filter('form[action="'.$this->url.'"]')->attr('method')));
        $this->assertContains($this->coOwnerId, $this->owners());
    }

    public function testConfirmingRejectsTheCoOwner(): void
    {
        $this->client->request('POST', $this->url);

        $this->assertResponseIsSuccessful();
        $this->assertNotContains($this->coOwnerId, $this->owners());
    }

    private function user(string $username): User
    {
        $person = new Person();
        $person->setName($username);
        $this->dm->persist($person);

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ACCESS_MULTIMEDIA_SERIES']);
        $user->setPerson($person);
        $this->dm->persist($user);

        return $user;
    }

    private function owners(): array
    {
        $this->dm->clear();

        return $this->dm->find(MultimediaObject::class, $this->multimediaObjectId)->getProperty('owners');
    }
}
