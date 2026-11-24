<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class MultimediaObjectPropertyTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private string $ownId;
    private string $otherId;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $role = new Role();
        $role->setCod('owner');
        $this->dm->persist($role);

        $person = new Person();
        $person->setName('personal');
        $this->dm->persist($person);

        $user = new User();
        $user->setUsername('personal');
        $user->setEmail('personal@pumukit.local');
        $user->setEnabled(true);
        $user->setRoles([PermissionProfile::SCOPE_PERSONAL, 'ROLE_ACCESS_MULTIMEDIA_SERIES']);
        $user->setPerson($person);
        $person->setUser($user);
        $this->dm->persist($user);

        $series = new Series();
        $series->setNumericalID(1);
        $this->dm->persist($series);

        $own = $this->multimediaObject($series, 1);
        $own->addPersonWithRole($person, $role);
        $other = $this->multimediaObject($series, 2);
        $this->dm->flush();

        $this->ownId = $own->getId();
        $this->otherId = $other->getId();
        $this->client->loginUser($user, 'pumukit');
        $this->dm->clear();
    }

    public function testAPersonalUserChangesTheLayoutOfItsOwnObject(): void
    {
        $this->client->request('POST', '/admin/mms/'.$this->ownId.'/updateProperty', ['paellalayout' => 'pip']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('pip', $this->layout($this->ownId));
    }

    public function testAPersonalUserCannotChangeTheLayoutOfAnotherObject(): void
    {
        $this->client->request('POST', '/admin/mms/'.$this->otherId.'/updateProperty', ['paellalayout' => 'pip']);

        $this->assertResponseStatusCodeSame(404);
        $this->assertNull($this->layout($this->otherId));
    }

    public function testAnUnknownObjectIsNotFound(): void
    {
        $this->client->request('POST', '/admin/mms/'.new ObjectId().'/updateProperty', ['paellalayout' => 'pip']);

        $this->assertResponseStatusCodeSame(404);
    }

    private function layout(string $id): ?string
    {
        $document = $this->dm->getDocumentCollection(MultimediaObject::class)->findOne(['_id' => new ObjectId($id)]);

        return $document['properties']['paellalayout'] ?? null;
    }

    private function multimediaObject(Series $series, int $numericalId): MultimediaObject
    {
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID($numericalId);
        $multimediaObject->setSeries($series);
        $this->dm->persist($multimediaObject);

        return $multimediaObject;
    }
}
