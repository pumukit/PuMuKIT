<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\NewAdminBundle\Services\UserStatsService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;

/**
 * @internal
 */
#[CoversNothing]
final class UserStatsServiceTest extends PumukitTestCase
{
    public function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        parent::setUp();
    }

    public function testTheStatsCountTheVideosOfTheConfiguredPersonalScopeRole(): void
    {
        $user = $this->userWithVideoAs('author');

        $stats = new UserStatsService($this->dm, 'author');

        self::assertSame([1, 0, 0], $stats->getUserMultimediaObjectsGroupByStats($user));
    }

    public function testTheStatsIgnoreTheVideosOfAnotherRole(): void
    {
        $user = $this->userWithVideoAs('owner');

        $stats = new UserStatsService($this->dm, 'author');

        self::assertSame([0, 0, 0], $stats->getUserMultimediaObjectsGroupByStats($user));
    }

    private function userWithVideoAs(string $roleCode): User
    {
        $role = new Role();
        $role->setCod($roleCode);
        $this->dm->persist($role);

        $person = new Person();
        $person->setName('Ana');
        $this->dm->persist($person);

        $user = new User();
        $user->setUsername('ana');
        $user->setEmail('ana@pumukit.local');
        $user->setPassword('unused');
        $user->setPerson($person);
        $this->dm->persist($user);

        $series = new Series();
        $series->setNumericalID(1);
        $this->dm->persist($series);

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setSeries($series);
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->addPersonWithRole($person, $role);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $user;
    }
}
