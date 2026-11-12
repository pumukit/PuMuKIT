<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\PersonalSeriesService;

/**
 * @internal
 */
#[CoversNothing]
class PersonalSeriesServiceTest extends PumukitTestCase
{
    private PersonalSeriesService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(PersonalSeriesService::class);

        $role = new Role();
        $role->setCod('owner');
        $this->dm->persist($role);
        $this->dm->flush();
    }

    public function testFindDoesNotCreateTheSeries(): void
    {
        $user = $this->user('teacher');

        $this->assertNull($this->service->find($user));
        $this->assertSame(0, $this->dm->getRepository(Series::class)->count());
    }

    public function testGetOrCreateCreatesTheSeriesOnceAndLinksItToTheUser(): void
    {
        $user = $this->user('teacher');

        $series = $this->service->getOrCreate($user);
        $again = $this->service->getOrCreate($user);

        $this->assertSame($series->getId(), $again->getId());
        $this->assertTrue($series->getProperty(PersonalSeriesService::DEFAULT_PERSONAL_SERIES_PROPERTY));
        $this->assertSame(1, $this->dm->getRepository(Series::class)->count());

        $this->dm->clear();
        $stored = $this->dm->getRepository(User::class)->find($user->getId());
        $this->assertSame($series->getId(), $stored->getPersonalSeries());
        $this->assertSame($series->getId(), $this->service->find($stored)?->getId());
    }

    public function testGetOrCreateTitlesTheSeriesInEveryLocale(): void
    {
        $series = $this->service->getOrCreate($this->user('teacher'));

        foreach ($series->getI18nTitle() as $title) {
            $this->assertStringEndsWith('teacher', $title);
        }
    }

    public function testTheTitleUsesTheFullNameWhenTheUserHasOne(): void
    {
        $user = $this->user('lti_8f3a');
        $user->setFullName('Ada Lovelace');
        $this->dm->flush();

        $series = $this->service->getOrCreate($user);

        foreach ($series->getI18nTitle() as $title) {
            $this->assertStringEndsWith('Ada Lovelace', $title);
            $this->assertStringNotContainsString('lti_8f3a', $title);
        }
    }

    public function testTheSeriesIsReplacedWhenTheUserIsNoLongerItsOwner(): void
    {
        $user = $this->user('teacher');
        $series = $this->service->getOrCreate($user);

        $other = static::getContainer()->get(FactoryService::class)->createSeries($this->user('other'));
        $user->setPersonalSeries($other->getId());
        $this->dm->flush();

        $this->assertNull($this->service->find($user));
        $this->assertNotSame($series->getId(), $this->service->getOrCreate($user)->getId());
    }

    public function testASeriesWithoutOwnersIsKept(): void
    {
        $user = $this->user('teacher');
        $series = static::getContainer()->get(FactoryService::class)->createSeries();
        $user->setPersonalSeries($series->getId());
        $this->dm->flush();

        $this->assertSame($series->getId(), $this->service->getOrCreate($user)->getId());
    }

    public function testTheSeriesIsReplacedWhenItWasDeleted(): void
    {
        $user = $this->user('teacher');
        $series = $this->service->getOrCreate($user);
        static::getContainer()->get(FactoryService::class)->deleteSeries($series);

        $this->assertNull($this->service->find($user));
        $this->assertNotSame($series->getId(), $this->service->getOrCreate($user)->getId());
    }

    private function user(string $username): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@example.com');
        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }
}
