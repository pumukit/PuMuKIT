<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\PersonalSeriesService;
use Pumukit\SchemaBundle\Services\PersonService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversNothing]
class PersonalSeriesCommandsTest extends PumukitTestCase
{
    private FactoryService $factory;
    private PersonalSeriesService $personalSeries;
    private Role $owner;

    public function setUp(): void
    {
        parent::setUp();
        $this->factory = static::getContainer()->get(FactoryService::class);
        $this->personalSeries = static::getContainer()->get(PersonalSeriesService::class);

        $this->owner = new Role();
        $this->owner->setCod('owner');
        $this->dm->persist($this->owner);
        $this->dm->flush();
    }

    public function testCleanOnlyCountsWithoutForce(): void
    {
        $this->personalSeries->getOrCreate($this->user('empty'));

        $tester = $this->tester('pumukit:personal-series:clean');
        $tester->execute(['--older-than' => '0']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('1 empty personal series found', $tester->getDisplay());
        $this->assertSame(1, $this->dm->getRepository(Series::class)->count());
    }

    public function testCleanDeletesOnlyTheEmptyPersonalSeries(): void
    {
        $empty = $this->user('empty');
        $emptySeries = $this->personalSeries->getOrCreate($empty);
        $full = $this->user('full');
        $fullSeries = $this->personalSeries->getOrCreate($full);
        $this->factory->createMultimediaObject($fullSeries, true, $full);
        $regular = $this->factory->createSeries($this->user('regular'));

        $tester = $this->tester('pumukit:personal-series:clean');
        $tester->execute(['--force' => true, '--older-than' => '0']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->dm->clear();
        $series = $this->dm->getRepository(Series::class);
        $this->assertNull($series->find($emptySeries->getId()));
        $this->assertNotNull($series->find($fullSeries->getId()));
        $this->assertNotNull($series->find($regular->getId()));
        $this->assertNull($this->dm->getRepository(User::class)->find($empty->getId())->getPersonalSeries());
        $this->assertSame($fullSeries->getId(), $this->dm->getRepository(User::class)->find($full->getId())->getPersonalSeries());
    }

    public function testCleanKeepsTheSeriesCreatedLessThanADayAgo(): void
    {
        $user = $this->user('uploading');
        $series = $this->personalSeries->getOrCreate($user);

        $tester = $this->tester('pumukit:personal-series:clean');
        $tester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('There are no empty personal series', $tester->getDisplay());
        $this->dm->clear();
        $this->assertNotNull($this->dm->getRepository(Series::class)->find($series->getId()));
        $this->assertSame($series->getId(), $this->dm->getRepository(User::class)->find($user->getId())->getPersonalSeries());
    }

    public function testCleanRejectsAnInvalidAge(): void
    {
        $tester = $this->tester('pumukit:personal-series:clean');
        $tester->execute(['--older-than' => '-1']);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
    }

    public function testMoveSendsEachObjectToThePersonalSeriesOfItsSingleOwner(): void
    {
        $shared = $this->factory->createSeries();
        $teacher = $this->user('teacher');
        $other = $this->user('other');
        $single = $this->video($shared, [$teacher]);
        $twoOwners = $this->video($shared, [$teacher, $other]);
        $noOwner = $this->video($shared, []);

        $tester = $this->tester('pumukit:personal-series:move');
        $tester->execute(['series' => $shared->getId()]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('2 owners', $tester->getDisplay());
        $this->assertStringContainsString('No owner', $tester->getDisplay());

        $this->dm->clear();
        $teacher = $this->dm->getRepository(User::class)->find($teacher->getId());
        $objects = $this->dm->getRepository(MultimediaObject::class);
        $this->assertSame($teacher->getPersonalSeries(), $objects->find($single->getId())->getSeries()->getId());
        $this->assertSame($shared->getId(), $objects->find($twoOwners->getId())->getSeries()->getId());
        $this->assertSame($shared->getId(), $objects->find($noOwner->getId())->getSeries()->getId());
        $this->assertNull($this->dm->getRepository(User::class)->find($other->getId())->getPersonalSeries());
    }

    public function testMoveDryRunChangesNothing(): void
    {
        $shared = $this->factory->createSeries();
        $teacher = $this->user('teacher');
        $video = $this->video($shared, [$teacher]);

        $tester = $this->tester('pumukit:personal-series:move');
        $tester->execute(['series' => $shared->getId(), '--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('1 multimedia objects would be moved', $tester->getDisplay());
        $this->dm->clear();
        $this->assertSame($shared->getId(), $this->dm->getRepository(MultimediaObject::class)->find($video->getId())->getSeries()->getId());
        $this->assertNull($this->dm->getRepository(User::class)->find($teacher->getId())->getPersonalSeries());
    }

    public function testMoveFailsWithAnUnknownSeries(): void
    {
        $tester = $this->tester('pumukit:personal-series:move');
        $tester->execute(['series' => '5f1b2c3d4e5f6a7b8c9d0e1f']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    private function video(Series $series, array $owners): MultimediaObject
    {
        $multimediaObject = $this->factory->createMultimediaObject($series);
        $personService = static::getContainer()->get(PersonService::class);
        foreach ($owners as $user) {
            $person = $personService->getPersonFromLoggedInUser($user);
            $personService->createRelationPerson($person, $this->owner, $multimediaObject);
        }

        return $multimediaObject;
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

    private function tester(string $name): CommandTester
    {
        return new CommandTester((new Application(self::$kernel))->find($name));
    }
}
