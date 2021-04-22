<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Repository;

use MongoDB\BSON\ObjectId;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\EmbeddedPerson;
use Pumukit\SchemaBundle\Document\EmbeddedRole;
use Pumukit\SchemaBundle\Document\EmbeddedTag;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\PersonInterface;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\RoleInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\User;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectRepositoryTest extends PumukitTestCase
{
    private $repo;
    private $qb;
    private $factoryService;
    private $mmsPicService;
    private $tagService;
    private $groupRepo;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->mmsPicService = static::$kernel->getContainer()->get('pumukitschema.mmspic');
        $this->tagService = static::$kernel->getContainer()->get('pumukitschema.tag');
        $this->groupRepo = $this->dm->getRepository(Group::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->factoryService = null;
        $this->mmsPicService = null;
        $this->tagService = null;
        gc_collect_cycles();
    }

    public function testRepositoryEmpty(): void
    {
        static::assertCount(0, $this->repo->findAll());
    }

    public function testRepository(): void
    {
        //$rank = 1;
        $status = MultimediaObject::STATUS_PUBLISHED;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $title = 'titulo cualquiera';
        $subtitle = 'Subtitle paragraph';
        $description = 'Description text';
        $duration = 300;

        $mmobj = new MultimediaObject();
        //$mmobj->setRank($rank);
        $mmobj->setNumericalID(1);
        $mmobj->setStatus($status);
        $mmobj->setRecordDate($record_date);
        $mmobj->setPublicDate($public_date);
        $mmobj->setTitle($title);
        $mmobj->setSubtitle($subtitle);
        $mmobj->setDescription($description);
        $mmobj->setDuration($duration);

        $this->dm->persist($mmobj);
        $this->dm->flush();

        static::assertCount(1, $this->repo->findAll());

        $t1 = new Track();
        $t1->setTags(['master']);
        $t2 = new Track();
        $t2->setTags(['mosca', 'master', 'old']);
        $t3 = new Track();
        $t3->setTags(['master', 'mosca']);
        $t4 = new Track();
        $t4->setTags(['flv', 'itunes', 'hide']);
        $t5 = new Track();
        $t5->setTags(['flv', 'webtv']);
        $t6 = new Track();
        $t6->setTags(['track6']);
        $t6->setHide(true);

        $this->dm->persist($t1);
        $this->dm->persist($t2);
        $this->dm->persist($t3);
        $this->dm->persist($t4);
        $this->dm->persist($t5);
        $this->dm->persist($t6);

        $mmobj->addTrack($t3);
        $mmobj->addTrack($t2);
        $mmobj->addTrack($t1);
        $mmobj->addTrack($t4);
        $mmobj->addTrack($t5);
        $mmobj->addTrack($t6);

        $this->dm->persist($mmobj);

        $this->dm->flush();

        static::assertCount(5, $mmobj->getFilteredTracksWithTags());
        static::assertCount(3, $mmobj->getFilteredTracksWithTags(['master']));
        static::assertCount(1, $mmobj->getFilteredTracksWithTags(['master'], ['mosca', 'old']));
        static::assertCount(0, $mmobj->getFilteredTracksWithTags([], ['mosca', 'old'], ['master']));
        static::assertCount(3, $mmobj->getFilteredTracksWithTags([], [], ['flv']));
        static::assertCount(0, $mmobj->getFilteredTracksWithTags([], [], ['flv', 'master']));
        static::assertCount(5, $mmobj->getFilteredTracksWithTags([], [], [], ['flv', 'master']));
        static::assertCount(1, $mmobj->getFilteredTracksWithTags(['mosca', 'old'], [], [], ['old']));
        static::assertCount(0, $mmobj->getFilteredTracksWithTags(['track6']));

        static::assertEquals($t3, $mmobj->getFilteredTrackWithTags());
        static::assertEquals($t3, $mmobj->getFilteredTrackWithTags(['master']));
        static::assertEquals($t2, $mmobj->getFilteredTrackWithTags(['master'], ['mosca', 'old']));
        static::assertEquals(null, $mmobj->getFilteredTrackWithTags([], ['mosca', 'old'], ['master']));
        static::assertEquals($t3, $mmobj->getFilteredTrackWithTags([], [], ['flv']));
        static::assertEquals(null, $mmobj->getFilteredTrackWithTags([], [], ['flv', 'master']));
        static::assertEquals($t3, $mmobj->getFilteredTrackWithTags([], [], [], ['flv', 'master']));
        static::assertEquals($t3, $mmobj->getFilteredTrackWithTags(['mosca', 'old'], [], [], ['old']));
        static::assertEquals($t1, $mmobj->getFilteredTrackWithTags([], [], [], ['master', 'mosca']));
        static::assertEquals(null, $mmobj->getFilteredTrackWithTags(['track6']));
    }

    public function testCreateMultimediaObjectAndFindByCriteria(): void
    {
        $series_type = $this->createSeriesType('Medieval Fantasy Sitcom');

        $series_main = $this->createSeries("Stark's growing pains");
        $series_wall = $this->createSeries('The Wall');
        $series_lhazar = $this->createSeries('A quiet life');

        $series_main->setSeriesType($series_type);
        $series_wall->setSeriesType($series_type);
        $series_lhazar->setSeriesType($series_type);

        $this->dm->persist($series_main);
        $this->dm->persist($series_wall);
        $this->dm->persist($series_lhazar);
        $this->dm->persist($series_type);
        $this->dm->flush();

        $person_ned = $this->createPerson('Ned');
        $person_benjen = $this->createPerson('Benjen');

        $role_lord = $this->createRole('Lord');
        $role_ranger = $this->createRole('Ranger');
        $role_hand = $this->createRole('Hand');

        $mm1 = $this->createMultimediaObjectAssignedToSeries('MmObject 1', $series_main);
        $mm1->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series_wall);
        $mm2->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm3 = $this->createMultimediaObjectAssignedToSeries('MmObject 3', $series_main);
        $mm3->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm4 = $this->createMultimediaObjectAssignedToSeries('MmObject 4', $series_lhazar);
        $mm4->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $mm1->addPersonWithRole($person_ned, $role_lord);
        $mm1->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $mm2->addPersonWithRole($person_benjen, $role_ranger);
        $mm2->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $mm3->addPersonWithRole($person_ned, $role_lord);
        $mm3->addPersonWithRole($person_benjen, $role_ranger);
        $mm3->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $mm4->addPersonWithRole($person_ned, $role_hand);
        $mm4->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();
        // DB setup END.

        // Test find by person
        $mmobj_ned = $this->repo->findByPersonId($person_ned->getId());
        static::assertCount(3, $mmobj_ned);

        // Test find by role cod or id
        $mmobj_lord = $this->repo->findByRoleCod($role_lord->getCod());
        $mmobj_ranger = $this->repo->findByRoleCod($role_ranger->getCod());
        $mmobj_hand = $this->repo->findByRoleCod($role_hand->getCod());
        static::assertCount(2, $mmobj_lord);
        static::assertCount(2, $mmobj_ranger);
        static::assertCount(1, $mmobj_hand);
        static::assertContains($mm1, $mmobj_lord);
        static::assertNotContains($mm2, $mmobj_lord);
        static::assertContains($mm3, $mmobj_lord);
        static::assertNotContains($mm4, $mmobj_lord);
        static::assertNotContains($mm1, $mmobj_ranger);
        static::assertContains($mm2, $mmobj_ranger);
        static::assertContains($mm3, $mmobj_ranger);
        static::assertNotContains($mm4, $mmobj_ranger);
        static::assertNotContains($mm1, $mmobj_hand);
        static::assertNotContains($mm2, $mmobj_hand);
        static::assertNotContains($mm3, $mmobj_hand);
        static::assertContains($mm4, $mmobj_hand);

        $mmobj_lord = $this->repo->findByRoleId($role_lord->getId());
        $mmobj_ranger = $this->repo->findByRoleId($role_ranger->getId());
        $mmobj_hand = $this->repo->findByRoleId($role_hand->getId());
        static::assertCount(2, $mmobj_lord);
        static::assertCount(2, $mmobj_ranger);
        static::assertCount(1, $mmobj_hand);
        static::assertContains($mm1, $mmobj_lord);
        static::assertNotContains($mm2, $mmobj_lord);
        static::assertContains($mm3, $mmobj_lord);
        static::assertNotContains($mm4, $mmobj_lord);
        static::assertNotContains($mm1, $mmobj_ranger);
        static::assertContains($mm2, $mmobj_ranger);
        static::assertContains($mm3, $mmobj_ranger);
        static::assertNotContains($mm4, $mmobj_ranger);
        static::assertNotContains($mm1, $mmobj_hand);
        static::assertNotContains($mm2, $mmobj_hand);
        static::assertNotContains($mm3, $mmobj_hand);
        static::assertContains($mm4, $mmobj_hand);

        // Test find by person and role
        $mmobj_benjen_ranger = $this->repo->findByPersonIdWithRoleCod($person_benjen->getId(), $role_ranger->getCod());
        $mmobj_ned_lord = $this->repo->findByPersonIdWithRoleCod($person_ned->getId(), $role_lord->getCod());
        $mmobj_ned_hand = $this->repo->findByPersonIdWithRoleCod($person_ned->getId(), $role_hand->getCod());
        $mmobj_benjen_lord = $this->repo->findByPersonIdWithRoleCod($person_benjen->getId(), $role_lord->getCod());
        $mmobj_ned_ranger = $this->repo->findByPersonIdWithRoleCod($person_ned->getId(), $role_ranger->getCod());
        $mmobj_benjen_hand = $this->repo->findByPersonIdWithRoleCod($person_benjen->getId(), $role_hand->getCod());

        static::assertCount(2, $mmobj_benjen_ranger);
        static::assertCount(2, $mmobj_ned_lord);
        static::assertCount(1, $mmobj_ned_hand);

        static::assertCount(0, $mmobj_benjen_lord);
        static::assertCount(0, $mmobj_ned_ranger);
        static::assertCount(0, $mmobj_benjen_hand);

        $seriesBenjen = $this->repo->findSeriesFieldByPersonId($person_benjen->getId());
        $seriesNed = $this->repo->findSeriesFieldByPersonId($person_ned->getId());

        static::assertCount(2, $seriesBenjen);
        $seriesBenjen = array_map(static function ($a) { return (string) $a; }, $seriesBenjen);
        static::assertContains($series_wall->getId(), $seriesBenjen);
        static::assertContains($series_main->getId(), $seriesBenjen);

        static::assertCount(2, $seriesNed);
        $seriesNed = array_map(static function ($a) { return (string) $a; }, $seriesNed);
        static::assertContains($series_main->getId(), $seriesNed);
        static::assertContains($series_lhazar->getId(), $seriesNed);

        $seriesBenjenRanger = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_benjen->getId(), $role_ranger->getCod());
        $seriesNedRanger = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_ned->getId(), $role_ranger->getCod());
        $seriesBenjenLord = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_benjen->getId(), $role_lord->getCod());
        $seriesNedLord = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_ned->getId(), $role_lord->getCod());
        $seriesBenjenHand = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_benjen->getId(), $role_hand->getCod());
        $seriesNedHand = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_ned->getId(), $role_hand->getCod());

        static::assertCount(2, $seriesBenjenRanger);
        $seriesBenjenRanger = array_map(static function ($a) { return (string) $a; }, $seriesBenjenRanger);
        static::assertContains($series_wall->getId(), $seriesBenjenRanger);
        static::assertContains($series_main->getId(), $seriesBenjenRanger);
        static::assertNotContains($series_lhazar->getId(), $seriesBenjenRanger);

        static::assertCount(0, $seriesNedRanger);
        $seriesNedRanger = array_map(static function ($a) { return (string) $a; }, $seriesNedRanger);
        static::assertNotContains($series_wall->getId(), $seriesNedRanger);
        static::assertNotContains($series_main->getId(), $seriesNedRanger);
        static::assertNotContains($series_lhazar->getId(), $seriesNedRanger);

        static::assertCount(0, $seriesBenjenLord);
        $seriesBenjenLord = array_map(static function ($a) { return (string) $a; }, $seriesBenjenLord);
        static::assertNotContains($series_wall->getId(), $seriesBenjenLord);
        static::assertNotContains($series_main->getId(), $seriesBenjenLord);
        static::assertNotContains($series_lhazar->getId(), $seriesBenjenLord);

        static::assertCount(1, $seriesNedLord);
        $seriesNedLord = array_map(static function ($a) { return (string) $a; }, $seriesNedLord);
        static::assertNotContains($series_wall->getId(), $seriesNedLord);
        static::assertContains($series_main->getId(), $seriesNedLord);
        static::assertNotContains($series_lhazar->getId(), $seriesNedLord);

        static::assertCount(0, $seriesBenjenHand);
        $seriesBenjenHand = array_map(static function ($a) { return (string) $a; }, $seriesBenjenHand);
        static::assertNotContains($series_wall->getId(), $seriesBenjenHand);
        static::assertNotContains($series_main->getId(), $seriesBenjenHand);
        static::assertNotContains($series_lhazar->getId(), $seriesBenjenHand);

        static::assertCount(1, $seriesNedHand);
        $seriesNedHand = array_map(static function ($a) { return (string) $a; }, $seriesNedHand);
        static::assertNotContains($series_wall->getId(), $seriesNedHand);
        static::assertNotContains($series_main->getId(), $seriesNedHand);
        static::assertContains($series_lhazar->getId(), $seriesNedHand);

        $mmobjsMainNedLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_ned->getId(), $role_lord->getCod());
        static::assertCount(2, $mmobjsMainNedLord);
        static::assertContains($mm1, $mmobjsMainNedLord);
        static::assertContains($mm3, $mmobjsMainNedLord);

        $mmobjsMainNedRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_ned->getId(), $role_ranger->getCod());
        static::assertCount(0, $mmobjsMainNedRanger);
        static::assertNotContains($mm1, $mmobjsMainNedRanger);
        static::assertNotContains($mm3, $mmobjsMainNedRanger);

        $mmobjsMainNedHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_ned->getId(), $role_hand->getCod());
        static::assertCount(0, $mmobjsMainNedHand);
        static::assertNotContains($mm1, $mmobjsMainNedHand);
        static::assertNotContains($mm3, $mmobjsMainNedHand);

        $mmobjsMainBenjenLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_benjen->getId(), $role_lord->getCod());
        static::assertCount(0, $mmobjsMainBenjenLord);
        static::assertNotContains($mm1, $mmobjsMainBenjenLord);
        static::assertNotContains($mm3, $mmobjsMainBenjenLord);

        $mmobjsMainBenjenRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_benjen->getId(), $role_ranger->getCod());
        static::assertCount(1, $mmobjsMainBenjenRanger);
        static::assertNotContains($mm1, $mmobjsMainBenjenRanger);
        static::assertContains($mm3, $mmobjsMainBenjenRanger);

        $mmobjsMainBenjenHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_benjen->getId(), $role_hand->getCod());
        static::assertCount(0, $mmobjsMainBenjenHand);
        static::assertNotContains($mm1, $mmobjsMainBenjenHand);
        static::assertNotContains($mm3, $mmobjsMainBenjenHand);

        $mmobjsWallNedLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_ned->getId(), $role_lord->getCod());
        static::assertCount(0, $mmobjsWallNedLord);
        static::assertNotContains($mm2, $mmobjsWallNedLord);

        $mmobjsWallNedRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_ned->getId(), $role_ranger->getCod());
        static::assertCount(0, $mmobjsWallNedRanger);
        static::assertNotContains($mm2, $mmobjsWallNedRanger);

        $mmobjsWallNedHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_ned->getId(), $role_hand->getCod());
        static::assertCount(0, $mmobjsWallNedHand);
        static::assertNotContains($mm2, $mmobjsWallNedHand);

        $mmobjsWallBenjenLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_benjen->getId(), $role_lord->getCod());
        static::assertCount(0, $mmobjsWallBenjenLord);
        static::assertNotContains($mm2, $mmobjsWallBenjenLord);

        $mmobjsWallBenjenRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_benjen->getId(), $role_ranger->getCod());
        static::assertCount(1, $mmobjsWallBenjenRanger);
        static::assertContains($mm2, $mmobjsWallBenjenRanger);

        $mmobjsWallBenjenHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_benjen->getId(), $role_hand->getCod());
        static::assertCount(0, $mmobjsWallBenjenHand);
        static::assertNotContains($mm2, $mmobjsWallBenjenHand);

        $mmobjsLhazarNedLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_ned->getId(), $role_lord->getCod());
        static::assertCount(0, $mmobjsLhazarNedLord);
        static::assertNotContains($mm4, $mmobjsLhazarNedLord);

        $mmobjsLhazarNedRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_ned->getId(), $role_ranger->getCod());
        static::assertCount(0, $mmobjsLhazarNedRanger);
        static::assertNotContains($mm4, $mmobjsLhazarNedRanger);

        $mmobjsLhazarNedHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_ned->getId(), $role_hand->getCod());
        static::assertCount(1, $mmobjsLhazarNedHand);
        static::assertContains($mm4, $mmobjsLhazarNedHand);

        $mmobjsLhazarBenjenLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_benjen->getId(), $role_lord->getCod());
        static::assertCount(0, $mmobjsLhazarBenjenLord);
        static::assertNotContains($mm4, $mmobjsLhazarBenjenLord);

        $mmobjsLhazarBenjenRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_benjen->getId(), $role_ranger->getCod());
        static::assertCount(0, $mmobjsLhazarBenjenRanger);
        static::assertNotContains($mm4, $mmobjsLhazarBenjenRanger);

        $mmobjsLhazarBenjenHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_benjen->getId(), $role_hand->getCod());
        static::assertCount(0, $mmobjsLhazarBenjenHand);
        static::assertNotContains($mm4, $mmobjsLhazarBenjenHand);

        // Test find by person id and role cod or groups
        $group1 = new Group();
        $group1->setKey('group1');
        $group1->setName('Group 1');
        $group2 = new Group();
        $group2->setKey('group2');
        $group2->setName('Group 2');
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();
        $mm1->addGroup($group1);
        $mm2->addGroup($group1);
        $mm3->addGroup($group1);
        $mm3->addGroup($group2);
        $mm4->addGroup($group2);
        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();

        $groups1 = [$group1->getId()];
        $groups2 = [$group2->getId()];

        $mmobj_benjen_ranger_group1 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_benjen->getId(), $role_ranger->getCod(), $groups1);
        $mmobj_benjen_ranger_group2 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_benjen->getId(), $role_ranger->getCod(), $groups2);
        $mmobj_ned_lord_group1 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_ned->getId(), $role_lord->getCod(), $groups1);
        $mmobj_ned_lord_group2 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_ned->getId(), $role_lord->getCod(), $groups2);
        $mmobj_ned_hand_group1 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_ned->getId(), $role_hand->getCod(), $groups1);
        $mmobj_ned_hand_group2 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_ned->getId(), $role_hand->getCod(), $groups2);
        $mmobj_benjen_lord_group1 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_benjen->getId(), $role_lord->getCod(), $groups1);
        $mmobj_benjen_lord_group2 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_benjen->getId(), $role_lord->getCod(), $groups2);
        $mmobj_ned_ranger_group1 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_ned->getId(), $role_ranger->getCod(), $groups1);
        $mmobj_ned_ranger_group2 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_ned->getId(), $role_ranger->getCod(), $groups2);
        $mmobj_benjen_hand_group1 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_benjen->getId(), $role_hand->getCod(), $groups1);
        $mmobj_benjen_hand_group2 = $this->repo->findByPersonIdAndRoleCodOrGroups($person_benjen->getId(), $role_hand->getCod(), $groups2);

        static::assertCount(3, $mmobj_benjen_ranger_group1);
        static::assertCount(3, $mmobj_ned_lord_group1);
        static::assertCount(4, $mmobj_ned_hand_group1);
        static::assertCount(3, $mmobj_benjen_ranger_group2);
        static::assertCount(3, $mmobj_ned_lord_group2);
        static::assertCount(2, $mmobj_ned_hand_group2);

        static::assertCount(3, $mmobj_benjen_lord_group1);
        static::assertCount(3, $mmobj_ned_ranger_group1);
        static::assertCount(3, $mmobj_benjen_hand_group1);
        static::assertCount(2, $mmobj_benjen_lord_group2);
        static::assertCount(2, $mmobj_ned_ranger_group2);
        static::assertCount(2, $mmobj_benjen_hand_group2);
    }

    public function testPeopleInMultimediaObjectCollection(): void
    {
        $personLucy = new Person();
        $personLucy->setName('Lucy');
        $personKate = new Person();
        $personKate->setName('Kate');
        $personPete = new Person();
        $personPete->setName('Pete');

        $roleActor = new Role();
        $roleActor->setCod('actor');
        $rolePresenter = new Role();
        $rolePresenter->setCod('presenter');
        $roleDirector = new Role();
        $roleDirector->setCod('director');

        $this->dm->persist($personLucy);
        $this->dm->persist($personKate);
        $this->dm->persist($personPete);

        $this->dm->persist($roleActor);
        $this->dm->persist($rolePresenter);
        $this->dm->persist($roleDirector);

        $mm = new MultimediaObject();
        $mm->setNumericalID(2);
        $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($mm->containsPerson($personKate));
        static::assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        static::assertCount(0, $mm->getPeople());
        static::assertFalse($mm->containsPersonWithAllRoles($personKate, [$roleActor, $rolePresenter, $roleDirector]));
        static::assertFalse($mm->containsPersonWithAnyRole($personKate, [$roleActor, $rolePresenter, $roleDirector]));

        $mm->addPersonWithRole($personKate, $roleActor);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($mm->containsPerson($personKate));
        static::assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        static::assertFalse($mm->containsPersonWithRole($personKate, $rolePresenter));
        static::assertFalse($mm->containsPersonWithRole($personKate, $roleDirector));
        static::assertFalse($mm->containsPerson($personLucy));
        static::assertCount(1, $mm->getPeople());
        static::assertEquals($personKate->getId(), $mm->getPersonWithRole($personKate, $roleActor)->getId());

        $mm2 = new MultimediaObject();
        $mm2->setNumericalID(3);
        $mm2->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertFalse($mm2->containsPerson($personKate));
        static::assertFalse($mm2->containsPersonWithRole($personKate, $roleActor));
        static::assertCount(0, $mm2->getPeople());

        static::assertNull($mm2->getPersonWithRole($personKate, $roleActor));

        $mm2->addPersonWithRole($personKate, $roleActor);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertTrue($mm2->containsPerson($personKate));
        static::assertTrue($mm2->containsPersonWithRole($personKate, $roleActor));
        static::assertFalse($mm2->containsPersonWithRole($personKate, $rolePresenter));
        static::assertFalse($mm2->containsPersonWithRole($personKate, $roleDirector));
        static::assertFalse($mm2->containsPerson($personLucy));
        static::assertCount(1, $mm2->getPeople());

        $mm->addPersonWithRole($personKate, $rolePresenter);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        static::assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        static::assertFalse($mm->containsPersonWithRole($personKate, $roleDirector));
        static::assertCount(2, $mm->getPeople());

        $mm->addPersonWithRole($personKate, $roleDirector);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        static::assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        static::assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        static::assertTrue($mm->containsPersonWithAllRoles($personKate, [$roleActor, $rolePresenter, $roleDirector]));
        static::assertTrue($mm->containsPersonWithAnyRole($personKate, [$roleActor, $rolePresenter, $roleDirector]));
        static::assertCount(3, $mm->getPeople());

        $mm->addPersonWithRole($personLucy, $roleDirector);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        static::assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        static::assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        static::assertTrue($mm->containsPerson($personLucy));
        static::assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        static::assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        static::assertTrue($mm->containsPersonWithRole($personLucy, $roleDirector));
        static::assertCount(4, $mm->getPeople());

        static::assertCount(4, $mm->getPeopleByRole(null, false));
        $mm->getEmbeddedRole($roleDirector)->setDisplay(false);
        $this->dm->persist($mm);
        $this->dm->flush();
        static::assertCount(4, $mm->getPeopleByRole(null, true));
        static::assertCount(2, $mm->getPeopleByRole(null, false));
        $mm->getEmbeddedRole($roleDirector)->setDisplay(true);
        $this->dm->persist($mm);
        $this->dm->flush();

        $peopleDirector = $mm->getPeopleByRole($roleDirector);
        static::assertEquals(
            [$personKate->getId(), $personLucy->getId()],
            [$peopleDirector[0]->getId(), $peopleDirector[1]->getId()]
        );

        $mm->downPersonWithRole($personKate, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleByRole($roleDirector);
        static::assertEquals(
            [$personLucy->getId(), $personKate->getId()],
            [$peopleDirector[0]->getId(), $peopleDirector[1]->getId()]
        );

        $mm->upPersonWithRole($personKate, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleByRole($roleDirector);
        static::assertEquals(
            [$personKate->getId(), $personLucy->getId()],
            [$peopleDirector[0]->getId(), $peopleDirector[1]->getId()]
        );

        $mm->upPersonWithRole($personLucy, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleByRole($roleDirector);
        static::assertEquals(
            [$personLucy->getId(), $personKate->getId()],
            [$peopleDirector[0]->getId(), $peopleDirector[1]->getId()]
        );

        $mm->downPersonWithRole($personLucy, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleByRole($roleDirector);
        static::assertEquals(
            [$personKate->getId(), $personLucy->getId()],
            [$peopleDirector[0]->getId(), $peopleDirector[1]->getId()]
        );

        static::assertCount(3, $mm->getAllEmbeddedPeopleByPerson($personKate));
        static::assertCount(1, $mm->getAllEmbeddedPeopleByPerson($personLucy));
        static::assertCount(1, $mm2->getAllEmbeddedPeopleByPerson($personKate));
        static::assertCount(0, $mm2->getAllEmbeddedPeopleByPerson($personLucy));

        static::assertTrue($mm->removePersonWithRole($personKate, $roleActor));
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        static::assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        static::assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        static::assertTrue($mm->containsPerson($personLucy));
        static::assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        static::assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        static::assertTrue($mm->containsPersonWithRole($personLucy, $roleDirector));
        static::assertCount(3, $mm->getPeople());

        static::assertTrue($mm->removePersonWithRole($personLucy, $roleDirector));
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        static::assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        static::assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        static::assertFalse($mm->containsPerson($personLucy));
        static::assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        static::assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        static::assertFalse($mm->containsPersonWithRole($personLucy, $roleDirector));
        static::assertCount(2, $mm->getPeople());

        static::assertTrue($mm->removePersonWithRole($personKate, $roleDirector));
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        static::assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        static::assertFalse($mm->containsPersonWithRole($personKate, $roleDirector));
        static::assertFalse($mm->containsPerson($personLucy));
        static::assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        static::assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        static::assertFalse($mm->containsPersonWithRole($personLucy, $roleDirector));
        static::assertCount(1, $mm->getPeople());

        static::assertFalse($mm->removePersonWithRole($personKate, $roleActor));
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertCount(1, $mm->getPeople());

        static::assertTrue($mm->removePersonWithRole($personKate, $rolePresenter));
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertCount(0, $mm->getPeople());
    }

    public function testGetAllEmbeddedRoleByPerson(): void
    {
        $personLucy = new Person();
        $personLucy->setName('Lucy');
        $personKate = new Person();
        $personKate->setName('Kate');
        $personPete = new Person();
        $personPete->setName('Pete');

        $roleActor = new Role();
        $roleActor->setCod('actor');
        $rolePresenter = new Role();
        $rolePresenter->setCod('presenter');
        $roleDirector = new Role();
        $roleDirector->setCod('director');

        $this->dm->persist($personLucy);
        $this->dm->persist($personKate);
        $this->dm->persist($personPete);

        $this->dm->persist($roleActor);
        $this->dm->persist($rolePresenter);
        $this->dm->persist($roleDirector);

        $mm = new MultimediaObject();
        $mm->setNumericalID(4);
        $this->dm->persist($mm);
        $this->dm->flush();

        $mm->addPersonWithRole($personKate, $roleActor);
        $mm->addPersonWithRole($personLucy, $roleActor);
        $mm->addPersonWithRole($personPete, $roleActor);
        $mm->addPersonWithRole($personKate, $rolePresenter);
        $mm->addPersonWithRole($personLucy, $rolePresenter);
        $mm->addPersonWithRole($personLucy, $roleDirector);
        $mm->addPersonWithRole($personPete, $roleDirector);
        $this->dm->persist($mm);
        $this->dm->flush();

        $kateQueryRolesIds = [];
        foreach ($mm->getAllEmbeddedRolesByPerson($personKate) as $embeddedRole) {
            $kateQueryRolesIds[] = $embeddedRole->getId();
        }

        static::assertContains($roleActor->getId(), $kateQueryRolesIds);
        static::assertContains($rolePresenter->getId(), $kateQueryRolesIds);
        static::assertNotContains($roleDirector->getId(), $kateQueryRolesIds);

        $lucyQueryRolesIds = [];
        foreach ($mm->getAllEmbeddedRolesByPerson($personLucy) as $embeddedRole) {
            $lucyQueryRolesIds[] = $embeddedRole->getId();
        }

        static::assertContains($roleActor->getId(), $lucyQueryRolesIds);
        static::assertContains($rolePresenter->getId(), $lucyQueryRolesIds);
        static::assertContains($roleDirector->getId(), $lucyQueryRolesIds);

        $peteQueryRolesIds = [];
        foreach ($mm->getAllEmbeddedRolesByPerson($personPete) as $embeddedRole) {
            $peteQueryRolesIds[] = $embeddedRole->getId();
        }

        static::assertContains($roleActor->getId(), $peteQueryRolesIds);
        static::assertNotContains($rolePresenter->getId(), $peteQueryRolesIds);
        static::assertContains($roleDirector->getId(), $peteQueryRolesIds);
    }

    public function testFindBySeries(): void
    {
        static::assertCount(0, $this->repo->findAll());

        $series1 = $this->createSeries('Series 1');
        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $series2 = $this->createSeries('Series 2');
        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);

        $series1 = $this->dm->find(Series::class, $series1->getId());
        $series2 = $this->dm->find(Series::class, $series2->getId());

        static::assertCount(4, $this->repo->findBySeries($series1));
        static::assertCount(3, $this->repo->findBySeries($series2));

        static::assertCount(3, $this->repo->findStandardBySeries($series1));
        static::assertCount(2, $this->repo->findStandardBySeries($series2));

        $tag1 = new Tag();
        $tag1->setCod('tag1');
        $tag2 = new Tag();
        $tag2->setCod('tag2');
        $tag3 = new Tag();
        $tag3->setCod('tag3');

        $mm11->addTag($tag1);
        $mm11->addTag($tag2);
        $mm12->addTag($tag3);
        $mm13->addTag($tag3);
        $mm21->addTag($tag1);
        $mm22->addTag($tag2);
        $mm22->addTag($tag3);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        static::assertCount(2, $this->repo->findBySeriesByTagCodAndStatus($series1, 'tag3'));
        static::assertCount(1, $this->repo->findBySeriesByTagCodAndStatus($series2, 'tag1'));
        static::assertCount(1, $this->repo->findBySeriesByTagCodAndStatus($series2, 'tag3'));
        static::assertCount(1, $this->repo->findBySeriesByTagCodAndStatus($series1, 'tag2'));
    }

    public function testFindWithStatus(): void
    {
        $series = $this->createSeries('Serie prueba status');

        $mmNew = $this->createMultimediaObjectAssignedToSeries('Status new', $series);
        $mmNew->setStatus(MultimediaObject::STATUS_NEW);

        $mmHide = $this->createMultimediaObjectAssignedToSeries('Status hide', $series);
        $mmHide->setStatus(MultimediaObject::STATUS_HIDDEN);

        $mmBloq = $this->createMultimediaObjectAssignedToSeries('Status bloq', $series);
        $mmBloq->setStatus(MultimediaObject::STATUS_BLOCKED);

        $mmPublished = $this->createMultimediaObjectAssignedToSeries('Status published', $series);
        $mmPublished->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $this->dm->persist($mmNew);
        $this->dm->persist($mmHide);
        $this->dm->persist($mmBloq);
        $this->dm->persist($mmPublished);
        $this->dm->flush();

        static::assertCount(1, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_PROTOTYPE]));
        static::assertCount(1, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_NEW]));
        static::assertCount(1, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_HIDDEN]));
        static::assertCount(1, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_BLOCKED]));
        static::assertCount(1, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_PUBLISHED]));
        static::assertCount(2, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_PROTOTYPE, MultimediaObject::STATUS_NEW]));
        static::assertCount(3, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_NEW, MultimediaObject::STATUS_HIDDEN]));

        $mmArray = [$mmNew];
        static::assertEquals($mmArray, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_NEW]));
        $mmArray = [$mmHide];
        static::assertEquals($mmArray, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_HIDDEN]));
        $mmArray = [$mmBloq];
        static::assertEquals($mmArray, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_BLOCKED]));
        $mmArray = [$mmPublished];
        static::assertEquals($mmArray, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_PUBLISHED]));
        $mmArray = [$mmNew, $mmHide, $mmPublished];
        static::assertEquals($mmArray, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_NEW, MultimediaObject::STATUS_HIDDEN]));
    }

    public function testFindPrototype(): void
    {
        $series = $this->createSeries('Serie prueba status');

        $mmNew = $this->createMultimediaObjectAssignedToSeries('Status new', $series);
        $mmNew->setStatus(MultimediaObject::STATUS_NEW);

        $mmHide = $this->createMultimediaObjectAssignedToSeries('Status hide', $series);
        $mmHide->setStatus(MultimediaObject::STATUS_HIDDEN);

        $mmBloq = $this->createMultimediaObjectAssignedToSeries('Status bloq', $series);
        $mmBloq->setStatus(MultimediaObject::STATUS_BLOCKED);

        $mmPublished = $this->createMultimediaObjectAssignedToSeries('Status published', $series);
        $mmPublished->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $this->dm->persist($mmNew);
        $this->dm->persist($mmHide);
        $this->dm->persist($mmBloq);
        $this->dm->persist($mmPublished);
        $this->dm->flush();

        static::assertInstanceOf(MultimediaObject::class, $this->repo->findPrototype($series));
        static::assertNotEquals($mmNew, $this->repo->findPrototype($series));
        static::assertNotEquals($mmHide, $this->repo->findPrototype($series));
        static::assertNotEquals($mmBloq, $this->repo->findPrototype($series));
        static::assertNotEquals($mmPublished, $this->repo->findPrototype($series));
    }

    public function testFindWithoutPrototype(): void
    {
        $series = $this->createSeries('Serie prueba status');

        $mmNew = $this->createMultimediaObjectAssignedToSeries('Status new', $series);
        $mmNew->setStatus(MultimediaObject::STATUS_NEW);

        $mmHide = $this->createMultimediaObjectAssignedToSeries('Status hide', $series);
        $mmHide->setStatus(MultimediaObject::STATUS_HIDDEN);

        $mmBloq = $this->createMultimediaObjectAssignedToSeries('Status bloq', $series);
        $mmBloq->setStatus(MultimediaObject::STATUS_BLOCKED);

        $mmPublished = $this->createMultimediaObjectAssignedToSeries('Status published', $series);
        $mmPublished->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $this->dm->persist($mmNew);
        $this->dm->persist($mmHide);
        $this->dm->persist($mmBloq);
        $this->dm->persist($mmPublished);
        $this->dm->flush();

        static::assertCount(4, $this->repo->findWithoutPrototype($series));

        $mmArray = [
            $mmNew,
            $mmHide,
            $mmBloq,
            $mmPublished,
        ];
        static::assertEquals($mmArray, iterator_to_array($this->repo->findWithoutPrototype($series)));
    }

    public function testEmbedPicsInMultimediaObject(): void
    {
        $pic1 = new Pic();
        $pic2 = new Pic();
        $pic3 = new Pic();
        $pic4 = new Pic();

        $this->dm->persist($pic1);
        $this->dm->persist($pic2);
        $this->dm->persist($pic3);
        $this->dm->persist($pic4);

        $mm = new MultimediaObject();
        $mm->setNumericalID(5);
        $mm->addPic($pic1);
        $mm->addPic($pic2);
        $mm->addPic($pic3);

        $this->dm->persist($mm);

        $this->dm->flush();

        static::assertEquals($mm, $this->repo->findByPicId($pic1->getId()));
        static::assertEquals(null, $this->repo->findByPicId($pic4->getId()));

        static::assertEquals($pic1, $this->repo->find($mm->getId())->getPicById($pic1->getId()));
        static::assertEquals($pic2, $this->repo->find($mm->getId())->getPicById($pic2->getId()));
        static::assertEquals($pic3, $this->repo->find($mm->getId())->getPicById($pic3->getId()));
        static::assertNull($this->repo->find($mm->getId())->getPicById(null));

        $mm->removePicById($pic2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $picsArray = [$pic1, $pic3];
        static::assertCount(count($picsArray), $this->repo->find($mm->getId())->getPics());
        static::assertEquals($picsArray, $this->repo->find($mm->getId())->getPics()->toArray());

        $mm->upPicById($pic3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $picsArray = [$pic3, $pic1];
        static::assertEquals($picsArray, $this->repo->find($mm->getId())->getPics()->toArray());

        $mm->downPicById($pic3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $picsArray = [$pic1, $pic3];
        static::assertEquals($picsArray, $this->repo->find($mm->getId())->getPics()->toArray());
    }

    public function testEmbedMaterialsInMultimediaObject(): void
    {
        $material1 = new Material();
        $material2 = new Material();
        $material3 = new Material();

        $this->dm->persist($material1);
        $this->dm->persist($material2);
        $this->dm->persist($material3);

        $mm = new MultimediaObject();
        $mm->setNumericalID(6);
        $mm->addMaterial($material1);
        $mm->addMaterial($material2);
        $mm->addMaterial($material3);

        $this->dm->persist($mm);

        $this->dm->flush();

        static::assertEquals($material1, $this->repo->find($mm->getId())->getMaterialById($material1->getId()));
        static::assertEquals($material2, $this->repo->find($mm->getId())->getMaterialById($material2->getId()));
        static::assertEquals($material3, $this->repo->find($mm->getId())->getMaterialById($material3->getId()));
        static::assertNull($this->repo->find($mm->getId())->getMaterialById(null));

        $mm->removeMaterialById($material2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $materialsArray = [$material1, $material3];
        static::assertCount(count($materialsArray), $this->repo->find($mm->getId())->getMaterials());
        static::assertEquals($materialsArray, $this->repo->find($mm->getId())->getMaterials()->toArray());

        $mm->upMaterialById($material3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $materialsArray = [$material3, $material1];
        static::assertEquals($materialsArray, $this->repo->find($mm->getId())->getMaterials()->toArray());

        $mm->downMaterialById($material3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $materialsArray = [$material1, $material3];
        static::assertEquals($materialsArray, $this->repo->find($mm->getId())->getMaterials()->toArray());
    }

    public function testEmbedLinksInMultimediaObject(): void
    {
        $link1 = new Link();
        $link2 = new Link();
        $link3 = new Link();

        $this->dm->persist($link1);
        $this->dm->persist($link2);
        $this->dm->persist($link3);

        $mm = new MultimediaObject();
        $mm->setNumericalID(7);
        $mm->addLink($link1);
        $mm->addLink($link2);
        $mm->addLink($link3);

        $this->dm->persist($mm);

        $this->dm->flush();

        static::assertEquals($link1, $this->repo->find($mm->getId())->getLinkById($link1->getId()));
        static::assertEquals($link2, $this->repo->find($mm->getId())->getLinkById($link2->getId()));
        static::assertEquals($link3, $this->repo->find($mm->getId())->getLinkById($link3->getId()));
        static::assertNull($this->repo->find($mm->getId())->getLinkById(null));

        $mm->removeLinkById($link2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $linksArray = [$link1, $link3];
        static::assertCount(count($linksArray), $this->repo->find($mm->getId())->getLinks());
        static::assertEquals($linksArray, $this->repo->find($mm->getId())->getLinks()->toArray());

        $mm->upLinkById($link3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $linksArray = [$link3, $link1];
        static::assertEquals($linksArray, $this->repo->find($mm->getId())->getLinks()->toArray());

        $mm->downLinkById($link3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $linksArray = [$link1, $link3];
        static::assertEquals($linksArray, $this->repo->find($mm->getId())->getLinks()->toArray());
    }

    public function testEmbedTracksInMultimediaObject(): void
    {
        $track1 = new Track();
        $track2 = new Track();
        $track3 = new Track();

        $this->dm->persist($track1);
        $this->dm->persist($track2);
        $this->dm->persist($track3);

        $mm = new MultimediaObject();
        $mm->setNumericalID(8);
        $mm->addTrack($track1);
        $mm->addTrack($track2);
        $mm->addTrack($track3);

        $this->dm->persist($mm);

        $this->dm->flush();

        static::assertEquals($track1, $this->repo->find($mm->getId())->getTrackById($track1->getId()));
        static::assertEquals($track2, $this->repo->find($mm->getId())->getTrackById($track2->getId()));
        static::assertEquals($track3, $this->repo->find($mm->getId())->getTrackById($track3->getId()));
        static::assertNull($this->repo->find($mm->getId())->getTrackById(null));

        $mm->removeTrackById($track2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $tracksArray = [$track1, $track3];
        static::assertCount(count($tracksArray), $this->repo->find($mm->getId())->getTracks());
        static::assertEquals($tracksArray, $this->repo->find($mm->getId())->getTracks()->toArray());

        $mm->upTrackById($track3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $tracksArray = [$track3, $track1];
        static::assertEquals($tracksArray, $this->repo->find($mm->getId())->getTracks()->toArray());

        $mm->downTrackById($track3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $tracksArray = [$track1, $track3];
        static::assertEquals($tracksArray, $this->repo->find($mm->getId())->getTracks()->toArray());
    }

    public function testFindMultimediaObjectsWithTags(): void
    {
        $tag1 = new Tag();
        $tag1->setCod('tag1');
        $tag2 = new Tag();
        $tag2->setCod('tag2');
        $tag2->setParent($tag1);
        $tag3 = new Tag();
        $tag3->setCod('tag3');

        $this->dm->persist($tag1);
        $this->dm->persist($tag2);
        $this->dm->persist($tag3);
        $this->dm->flush();

        $series1 = $this->createSeries('Series 1');
        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $series2 = $this->createSeries('Series 2');
        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm23 = $this->factoryService->createMultimediaObject($series2);

        $series3 = $this->createSeries('Series 3');
        $mm31 = $this->factoryService->createMultimediaObject($series3);
        $mm32 = $this->factoryService->createMultimediaObject($series3);
        $mm33 = $this->factoryService->createMultimediaObject($series3);
        $mm34 = $this->factoryService->createMultimediaObject($series3);

        $mm11->addTag($tag1);
        $mm11->addTag($tag2);

        $mm12->addTag($tag1);

        $mm21->addTag($tag2);

        $mm22->addTag($tag1);

        $mm23->addTag($tag1);

        $mm31->addTag($tag1);

        $mm33->addTag($tag1);

        $mm34->addTag($tag1);

        $mm11->setTitle('mm11');
        $mm12->setTitle('mm12');
        $mm13->setTitle('mm13');
        $mm21->setTitle('mm21');
        $mm22->setTitle('mm22');
        $mm23->setTitle('mm23');
        $mm31->setTitle('mm31');
        $mm32->setTitle('mm32');
        $mm33->setTitle('mm33');
        $mm34->setTitle('mm34');

        $mm11->setPublicDate(new \DateTime('2015-01-03 15:05:16'));
        $mm12->setPublicDate(new \DateTime('2015-01-04 15:05:16'));
        $mm13->setPublicDate(new \DateTime('2015-01-05 15:05:16'));
        $mm21->setPublicDate(new \DateTime('2015-01-06 15:05:16'));
        $mm22->setPublicDate(new \DateTime('2015-01-07 15:05:16'));
        $mm23->setPublicDate(new \DateTime('2015-01-08 15:05:16'));
        $mm31->setPublicDate(new \DateTime('2015-01-09 15:05:16'));
        $mm32->setPublicDate(new \DateTime('2015-01-10 15:05:16'));
        $mm33->setPublicDate(new \DateTime('2015-01-11 15:05:16'));
        $mm34->setPublicDate(new \DateTime('2015-01-12 15:05:16'));

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->persist($mm23);
        $this->dm->persist($mm31);
        $this->dm->persist($mm32);
        $this->dm->persist($mm33);
        $this->dm->persist($mm34);
        $this->dm->flush();

        // SORT
        $sort = [];
        $sortAsc = ['public_date' => 1];
        $sortDesc = ['public_date' => -1];

        // FIND WITH TAG
        static::assertCount(7, $this->repo->findWithTag($tag1));
        $limit = 3;
        static::assertCount(3, $this->repo->findWithTag($tag1, $sort, $limit));
        $page = 0;
        static::assertCount(3, $this->repo->findWithTag($tag1, $sort, $limit, $page));
        $page = 1;
        static::assertCount(3, $this->repo->findWithTag($tag1, $sort, $limit, $page));
        $page = 2;
        static::assertCount(1, $this->repo->findWithTag($tag1, $sort, $limit, $page));
        $page = 3;
        static::assertCount(0, $this->repo->findWithTag($tag1, $sort, $limit, $page));

        // FIND WITH TAG (SORT)
        $page = 1;
        $arrayAsc = [$mm23, $mm31, $mm33];
        static::assertEquals($arrayAsc, $this->repo->findWithTag($tag1, $sortAsc, $limit, $page)->toArray());
        $arrayDesc = [$mm23, $mm22, $mm12];
        static::assertEquals($arrayDesc, $this->repo->findWithTag($tag1, $sortDesc, $limit, $page)->toArray());

        static::assertCount(2, $this->repo->findWithTag($tag2));
        $limit = 1;
        static::assertCount(1, $this->repo->findWithTag($tag2, $sort, $limit));
        $page = 0;
        static::assertCount(1, $this->repo->findWithTag($tag2, $sort, $limit, $page));
        $page = 1;
        static::assertCount(1, $this->repo->findWithTag($tag2, $sort, $limit, $page));

        //FIND WITH GENERAL TAG
        static::assertCount(7, $this->repo->findWithGeneralTag($tag1));
        $limit = 3;
        static::assertCount(3, $this->repo->findWithGeneralTag($tag1, $sort, $limit));
        $page = 1;
        static::assertCount(3, $this->repo->findWithGeneralTag($tag1, $sort, $limit, $page));
        static::assertCount(2, $this->repo->findWithGeneralTag($tag2));
        static::assertCount(0, $this->repo->findWithGeneralTag($tag3));
        //FIND WITH GENERAL TAG (SORT)
        $arrayAsc = [$mm23, $mm31, $mm33];
        static::assertEquals($arrayAsc, $this->repo->findWithGeneralTag($tag1, $sortAsc, $limit, $page)->toArray());
        $arrayDesc = [$mm23, $mm22, $mm12];
        static::assertEquals($arrayDesc, $this->repo->findWithGeneralTag($tag1, $sortDesc, $limit, $page)->toArray());

        // FIND ONE WITH TAG
        static::assertEquals($mm11, $this->repo->findOneWithTag($tag1));

        // FIND WITH ANY TAG
        $arrayTags = [$tag1, $tag2, $tag3];
        static::assertCount(8, $this->repo->findWithAnyTag($arrayTags));
        $limit = 3;
        static::assertCount(3, $this->repo->findWithAnyTag($arrayTags, $sort, $limit));
        $page = 0;
        static::assertCount(3, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page));
        $page = 1;
        static::assertCount(3, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page));
        $page = 2;
        static::assertCount(2, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page));

        // FIND WITH ANY TAG (SORT)
        $arrayAsc = [$mm11, $mm12, $mm21, $mm22, $mm23, $mm31, $mm33, $mm34];
        static::assertEquals($arrayAsc, $this->repo->findWithAnyTag($arrayTags, $sortAsc)->toArray());
        $limit = 3;
        $arrayAsc = [$mm11, $mm12, $mm21];
        static::assertEquals($arrayAsc, $this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit)->toArray());
        $page = 0;
        $arrayAsc = [$mm11, $mm12, $mm21];
        static::assertEquals($arrayAsc, $this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit, $page)->toArray());
        $page = 1;
        $arrayAsc = [$mm22, $mm23, $mm31];
        static::assertEquals($arrayAsc, $this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit, $page)->toArray());
        $page = 2;
        $arrayAsc = [$mm33, $mm34];
        static::assertEquals($arrayAsc, $this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit, $page)->toArray());

        $arrayDesc = [$mm34, $mm33, $mm31, $mm23, $mm22, $mm21, $mm12, $mm11];
        static::assertEquals($arrayDesc, $this->repo->findWithAnyTag($arrayTags, $sortDesc)->toArray());
        $limit = 5;
        $page = 0;
        $arrayDesc = [$mm34, $mm33, $mm31, $mm23, $mm22];
        static::assertEquals($arrayDesc, $this->repo->findWithAnyTag($arrayTags, $sortDesc, $limit, $page)->toArray());
        $page = 1;
        $arrayDesc = [$mm21, $mm12, $mm11];
        static::assertEquals($arrayDesc, $this->repo->findWithAnyTag($arrayTags, $sortDesc, $limit, $page)->toArray());

        // Add more tags
        $mm32->addTag($tag3);
        $this->dm->persist($mm32);
        $this->dm->flush();
        static::assertCount(9, $this->repo->findWithAnyTag($arrayTags));

        $arrayTags = [$tag2, $tag3];
        static::assertCount(3, $this->repo->findWithAnyTag($arrayTags));

        // FIND WITH ALL TAGS
        $mm32->addTag($tag2);

        $mm13->addTag($tag1);
        $mm13->addTag($tag2);

        $this->dm->persist($mm13);
        $this->dm->persist($mm32);
        $this->dm->flush();

        $arrayTags = [$tag1, $tag2];
        static::assertCount(2, $this->repo->findWithAllTags($arrayTags));

        $mm12->addTag($tag2);
        $mm22->addTag($tag2);
        $this->dm->persist($mm12);
        $this->dm->persist($mm22);
        $this->dm->flush();

        static::assertCount(4, $this->repo->findWithAllTags($arrayTags));
        $limit = 3;
        static::assertCount(3, $this->repo->findWithAllTags($arrayTags, $sort, $limit));
        $page = 0;
        static::assertCount(3, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page));
        $page = 1;
        static::assertCount(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page));

        $arrayTags = [$tag2, $tag3];
        static::assertCount(1, $this->repo->findWithAllTags($arrayTags));

        // FIND WITH ALL TAGS (SORT)
        $arrayTags = [$tag1, $tag2];
        $arrayAsc = [$mm11, $mm12, $mm13, $mm22];
        static::assertEquals($arrayAsc, $this->repo->findWithAllTags($arrayTags, $sortAsc)->toArray());
        $arrayDesc = [$mm22, $mm13, $mm12, $mm11];
        static::assertEquals($arrayDesc, $this->repo->findWithAllTags($arrayTags, $sortDesc)->toArray());
        $limit = 3;
        $arrayAsc = [$mm11, $mm12, $mm13];
        static::assertEquals($arrayAsc, $this->repo->findWithAllTags($arrayTags, $sortAsc, $limit)->toArray());
        $page = 1;
        $arrayAsc = [$mm22];
        static::assertEquals($arrayAsc, $this->repo->findWithAllTags($arrayTags, $sortAsc, $limit, $page)->toArray());

        $limit = 2;
        $page = 1;
        $arrayDesc = [$mm12, $mm11];
        static::assertEquals($arrayDesc, $this->repo->findWithAllTags($arrayTags, $sortDesc, $limit, $page)->toArray());

        // FIND ONE WITH ALL TAGS
        $arrayTags = [$tag1, $tag2];
        static::assertEquals($mm11, $this->repo->findOneWithAllTags($arrayTags));

        // FIND WITHOUT TAG
        static::assertCount(9, $this->repo->findWithoutTag($tag3));
        $limit = 4;
        static::assertCount(4, $this->repo->findWithoutTag($tag3, $sort, $limit));
        $page = 0;
        static::assertCount(4, $this->repo->findWithoutTag($tag3, $sort, $limit, $page));
        $page = 1;
        static::assertCount(4, $this->repo->findWithoutTag($tag3, $sort, $limit, $page));
        $page = 2;
        static::assertCount(1, $this->repo->findWithoutTag($tag3, $sort, $limit, $page));
        $page = 3;
        static::assertCount(0, $this->repo->findWithoutTag($tag3, $sort, $limit, $page));

        // FIND WITHOUT TAG (SORT)
        $arrayAsc = [$mm11, $mm12, $mm13, $mm21, $mm22, $mm23, $mm31, $mm33, $mm34];
        static::assertEquals($arrayAsc, $this->repo->findWithoutTag($tag3, $sortAsc)->toArray());
        $limit = 6;
        $arrayAsc = [$mm11, $mm12, $mm13, $mm21, $mm22, $mm23];
        static::assertEquals($arrayAsc, $this->repo->findWithoutTag($tag3, $sortAsc, $limit)->toArray());
        $page = 1;
        $arrayAsc = [$mm31, $mm33, $mm34];
        static::assertEquals($arrayAsc, $this->repo->findWithoutTag($tag3, $sortAsc, $limit, $page)->toArray());

        $arrayDesc = [$mm13, $mm12, $mm11];
        static::assertEquals($arrayDesc, $this->repo->findWithoutTag($tag3, $sortDesc, $limit, $page)->toArray());

        // FIND ONE WITHOUT TAG
        static::assertEquals($mm23, $this->repo->findOneWithoutTag($tag2));

        // FIND WITH ALL TAGS

        // FIND WITHOUT ALL TAGS
        $arrayTags = [$tag2, $tag3];
        static::assertCount(4, $this->repo->findWithoutAllTags($arrayTags));
        $limit = 3;
        static::assertCount(3, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit));
        $page = 0;
        static::assertCount(3, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit, $page));
        $page = 1;
        static::assertCount(1, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit, $page));

        $arrayTags = [$tag1, $tag3];
        static::assertCount(1, $this->repo->findWithoutAllTags($arrayTags));

        $arrayTags = [$tag1, $tag2];
        static::assertCount(0, $this->repo->findWithoutAllTags($arrayTags));

        // FIND WITHOUT ALL TAGS (SORT)
        $arrayTags = [$tag2, $tag3];
        $arrayAsc = [$mm23, $mm31, $mm33, $mm34];
        static::assertEquals($arrayAsc, $this->repo->findWithoutAllTags($arrayTags, $sortAsc)->toArray());
        $limit = 3;
        $page = 1;
        $arrayAsc = [$mm34];
        static::assertEquals($arrayAsc, $this->repo->findWithoutAllTags($arrayTags, $sortAsc, $limit, $page)->toArray());

        $page = 0;
        $arrayDesc = [$mm34, $mm33, $mm31];
        static::assertEquals($arrayDesc, $this->repo->findWithoutAllTags($arrayTags, $sortDesc, $limit, $page)->toArray());
    }

    public function testFindSeriesFieldWithTags(): void
    {
        $tag1 = new Tag();
        $tag1->setCod('tag1');
        $tag2 = new Tag();
        $tag2->setCod('tag2');
        $tag3 = new Tag();
        $tag3->setCod('tag3');

        $this->dm->persist($tag1);
        $this->dm->persist($tag2);
        $this->dm->persist($tag3);
        $this->dm->flush();

        $series1 = $this->createSeries('Series 1');
        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $series2 = $this->createSeries('Series 2');
        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm23 = $this->factoryService->createMultimediaObject($series2);

        $series3 = $this->createSeries('Series 3');
        $mm31 = $this->factoryService->createMultimediaObject($series3);
        $mm32 = $this->factoryService->createMultimediaObject($series3);
        $mm33 = $this->factoryService->createMultimediaObject($series3);
        $mm34 = $this->factoryService->createMultimediaObject($series3);

        $mm11->addTag($tag1);
        $mm11->addTag($tag2);

        $mm12->addTag($tag1);
        $mm12->addTag($tag2);

        $mm13->addTag($tag1);
        $mm13->addTag($tag2);

        $mm21->addTag($tag2);

        $mm22->addTag($tag1);
        $mm22->addTag($tag2);

        $mm23->addTag($tag1);

        $mm31->addTag($tag1);

        $mm32->addTag($tag2);
        $mm32->addTag($tag3);

        $mm33->addTag($tag1);

        $mm34->addTag($tag1);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->persist($mm23);
        $this->dm->persist($mm31);
        $this->dm->persist($mm32);
        $this->dm->persist($mm33);
        $this->dm->persist($mm34);
        $this->dm->flush();

        // FIND SERIES FIELD WITH TAG
        static::assertCount(3, $this->repo->findSeriesFieldWithTag($tag1));
        static::assertCount(1, $this->repo->findSeriesFieldWithTag($tag3));

        // FIND ONE SERIES FIELD WITH TAG
        static::assertEquals($series3->getId(), $this->repo->findOneSeriesFieldWithTag($tag3));

        // FIND SERIES FIELD WITH ANY TAG
        $arrayTags = [$tag1, $tag2];
        static::assertCount(3, $this->repo->findSeriesFieldWithAnyTag($arrayTags));

        $arrayTags = [$tag3];
        static::assertCount(1, $this->repo->findSeriesFieldWithAnyTag($arrayTags));

        // FIND SERIES FIELD WITH ALL TAGS
        $arrayTags = [$tag1, $tag2];
        static::assertCount(2, $this->repo->findSeriesFieldWithAllTags($arrayTags));

        $arrayTags = [$tag2, $tag3];
        static::assertCount(1, $this->repo->findSeriesFieldWithAllTags($arrayTags));

        // FIND ONE SERIES FIELD WITH ALL TAGS
        $arrayTags = [$tag1, $tag2];
        static::assertEquals($series1->getId(), $this->repo->findOneSeriesFieldWithAllTags($arrayTags));

        $arrayTags = [$tag2, $tag3];
        static::assertEquals($series3->getId(), $this->repo->findOneSeriesFieldWithAllTags($arrayTags));
    }

    public function testFindDistinctPics(): void
    {
        $pic1 = new Pic();
        $url1 = 'http://domain.com/pic1.png';
        $pic1->setUrl($url1);

        $pic2 = new Pic();
        $url2 = 'http://domain.com/pic2.png';
        $pic2->setUrl($url2);

        $pic3 = new Pic();
        $url3 = 'http://domain.com/pic3.png';
        $pic3->setUrl($url3);

        $pic4 = new Pic();
        $pic4->setUrl($url3);

        $pic5 = new Pic();
        $url5 = 'http://domain.com/pic5.png';
        $pic5->setUrl($url5);

        $this->dm->persist($pic1);
        $this->dm->persist($pic2);
        $this->dm->persist($pic3);
        $this->dm->persist($pic4);
        $this->dm->persist($pic5);
        $this->dm->flush();

        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');

        $series1 = $this->dm->find(Series::class, $series1->getId());
        $series2 = $this->dm->find(Series::class, $series2->getId());

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);

        $mm21 = $this->factoryService->createMultimediaObject($series2);

        $mm11->setTitle('mm11');
        $mm11 = $this->mmsPicService->addPicUrl($mm11, $pic1);
        $mm11 = $this->mmsPicService->addPicUrl($mm11, $pic2);
        $mm11 = $this->mmsPicService->addPicUrl($mm11, $pic4);

        $mm12->setTitle('mm12');
        $mm12 = $this->mmsPicService->addPicUrl($mm12, $pic3);

        $mm21->setTitle('mm21');
        $mm21 = $this->mmsPicService->addPicUrl($mm21, $pic5);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm21);
        $this->dm->flush();

        static::assertCount(3, $mm11->getPics());
        static::assertCount(3, $this->repo->find($mm11->getId())->getPics());
        static::assertCount(1, $mm12->getPics());
        static::assertCount(1, $this->repo->find($mm12->getId())->getPics());
        static::assertCount(1, $mm21->getPics());
        static::assertCount(1, $this->repo->find($mm21->getId())->getPics());

        static::assertCount(3, $this->repo->findDistinctUrlPicsInSeries($series1));

        static::assertCount(4, $this->repo->findDistinctUrlPics());

        $mm11->setPublicDate(new \DateTime('2015-01-03 15:05:16'));
        $mm12->setPublicDate(new \DateTime('2015-01-03 15:05:20'));
        $mm21->setPublicDate(new \DateTime('2015-01-03 15:05:25'));

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm21);
        $this->dm->flush();

        $arrayPics = [$pic1->getUrl(), $pic2->getUrl(), $pic3->getUrl(), $pic5->getUrl()];
        static::assertEquals($arrayPics, $this->repo->findDistinctUrlPics());

        $mm11->setPublicDate(new \DateTime('2015-01-13 15:05:16'));
        $mm12->setPublicDate(new \DateTime('2015-01-23 15:05:20'));
        $mm21->setPublicDate(new \DateTime('2015-01-03 15:05:25'));

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm21);
        $this->dm->flush();

        $arrayPics = [$pic1->getUrl(), $pic2->getUrl(), $pic3->getUrl(), $pic5->getUrl()];
        static::assertEquals($arrayPics, $this->repo->findDistinctUrlPics());
    }

    public function testFindOrderedBy(): void
    {
        $series = $this->createSeries('Series');
        $mm1 = $this->factoryService->createMultimediaObject($series);
        $mm2 = $this->factoryService->createMultimediaObject($series);
        $mm3 = $this->factoryService->createMultimediaObject($series);

        $mm1->setTitle('mm1');
        $mm2->setTitle('mm2');
        $mm3->setTitle('mm3');

        $mm1->setPublicDate(new \DateTime('2015-01-03 15:05:16'));
        $mm2->setPublicDate(new \DateTime('2015-01-04 15:05:16'));
        $mm3->setPublicDate(new \DateTime('2015-01-05 15:05:16'));

        $mm1->setRecordDate(new \DateTime('2015-01-04 15:05:16'));
        $mm2->setRecordDate(new \DateTime('2015-01-05 15:05:16'));
        $mm3->setRecordDate(new \DateTime('2015-01-03 15:05:16'));

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->flush();

        $sort = [];
        $sortPubDateAsc = ['public_date' => 'asc'];
        $sortPubDateDesc = ['public_date' => 'desc'];
        $sortRecDateAsc = ['record_date' => 'asc'];
        $sortRecDateDesc = ['record_date' => 'desc'];

        static::assertCount(3, $this->repo->findOrderedBy($series, $sort));
        static::assertCount(3, $this->repo->findOrderedBy($series, $sortPubDateAsc));
        static::assertCount(3, $this->repo->findOrderedBy($series, $sortPubDateDesc));
        static::assertCount(3, $this->repo->findOrderedBy($series, $sortRecDateAsc));
        static::assertCount(3, $this->repo->findOrderedBy($series, $sortRecDateDesc));

        $arrayMms = [$mm1, $mm2, $mm3];
        static::assertEquals($arrayMms, $this->repo->findOrderedBy($series, $sortPubDateAsc));
        $arrayMms = [$mm3, $mm2, $mm1];
        static::assertEquals($arrayMms, $this->repo->findOrderedBy($series, $sortPubDateDesc));
        $arrayMms = [$mm3, $mm1, $mm2];
        static::assertEquals($arrayMms, $this->repo->findOrderedBy($series, $sortRecDateAsc));
        $arrayMms = [$mm2, $mm1, $mm3];
        static::assertEquals($arrayMms, $this->repo->findOrderedBy($series, $sortRecDateDesc));
    }

    public function testEmbeddedTagChildOfTag(): void
    {
        $tag1 = new Tag();
        $tag1->setCod('tag1');
        $tag2 = new Tag();
        $tag2->setCod('tag2');
        $tag3 = new Tag();
        $tag3->setCod('tag3');

        $tag2->setParent($tag1);
        $tag3->setParent($tag2);

        $tag4 = new Tag();
        $tag4->setCod('tag4');

        $this->dm->persist($tag1);
        $this->dm->persist($tag2);
        $this->dm->persist($tag3);
        $this->dm->persist($tag4);
        $this->dm->flush();

        static::assertTrue($tag3->isChildOf($tag2));
        static::assertFalse($tag3->isChildOf($tag1));
        static::assertFalse($tag3->isChildOf($tag4));

        static::assertTrue($tag2->isChildOf($tag1));
        static::assertFalse($tag1->isChildOf($tag2));

        $series = $this->createSeries('Series');
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $tagAdded = $this->tagService->addTagToMultimediaObject($multimediaObject, $tag3->getId());

        $embeddedTags = $multimediaObject->getTags();
        $embeddedTag1 = $embeddedTags[2];
        $embeddedTag2 = $embeddedTags[1];
        $embeddedTag3 = $embeddedTags[0];

        static::assertTrue($embeddedTag3->isChildOf($tag2));
        static::assertTrue($embeddedTag2->isChildOf($tag1));

        static::assertTrue($embeddedTag3->isChildOf($embeddedTag2));
        static::assertTrue($embeddedTag2->isChildOf($embeddedTag1));

        static::assertFalse($embeddedTag3->isChildOf($tag1));
        static::assertFalse($embeddedTag3->isChildOf($embeddedTag1));

        static::assertFalse($tag1->isChildOf($embeddedTag3));
        static::assertFalse($embeddedTag1->isChildOf($embeddedTag3));

        static::assertFalse($embeddedTag1->isChildOf($tag4));
        static::assertFalse($embeddedTag2->isChildOf($tag4));
        static::assertFalse($embeddedTag3->isChildOf($tag4));
    }

    public function testCountInSeries(): void
    {
        $series1 = new Series();
        $series1->setNumericalID(111);
        $series2 = new Series();
        $series2->setNumericalID(222);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $mm11 = new MultimediaObject();
        $mm11->setNumericalID(11);
        $mm12 = new MultimediaObject();
        $mm12->setNumericalID(12);
        $mm13 = new MultimediaObject();
        $mm13->setNumericalID(13);

        $mm21 = new MultimediaObject();
        $mm21->setNumericalID(21);
        $mm22 = new MultimediaObject();
        $mm22->setNumericalID(22);
        $mm23 = new MultimediaObject();
        $mm23->setNumericalID(23);
        $mm24 = new MultimediaObject();
        $mm24->setNumericalID(24);

        $mm11->setSeries($series1);
        $mm12->setSeries($series1);
        $mm13->setSeries($series1);

        $mm21->setSeries($series2);
        $mm22->setSeries($series2);
        $mm23->setSeries($series2);
        $mm24->setSeries($series2);

        $mm11->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm12->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm13->setStatus(MultimediaObject::STATUS_PROTOTYPE);

        $mm21->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm22->setStatus(MultimediaObject::STATUS_BLOCKED);
        $mm23->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        $mm24->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->persist($mm23);
        $this->dm->persist($mm24);
        $this->dm->flush();

        static::assertEquals(2, $this->repo->countInSeries($series1));
        static::assertEquals(3, $this->repo->countInSeries($series2));
    }

    public function testCountPeopleWithRoleCode(): void
    {
        $series_type = $this->createSeriesType('Medieval Fantasy Sitcom');

        $series_main = $this->createSeries("Stark's growing pains");
        $series_wall = $this->createSeries('The Wall');
        $series_lhazar = $this->createSeries('A quiet life');

        $series_main->setSeriesType($series_type);
        $series_wall->setSeriesType($series_type);
        $series_lhazar->setSeriesType($series_type);

        $this->dm->persist($series_main);
        $this->dm->persist($series_wall);
        $this->dm->persist($series_lhazar);
        $this->dm->persist($series_type);
        $this->dm->flush();

        $person_ned = $this->createPerson('Ned');
        $person_benjen = $this->createPerson('Benjen');
        $person_mark = $this->createPerson('Mark');
        $person_catherin = $this->createPerson('Ned');

        $role_lord = $this->createRole('Lord');
        $role_ranger = $this->createRole('Ranger');
        $role_hand = $this->createRole('Hand');

        $mm1 = $this->createMultimediaObjectAssignedToSeries('MmObject 1', $series_main);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series_wall);
        $mm3 = $this->createMultimediaObjectAssignedToSeries('MmObject 3', $series_main);
        $mm4 = $this->createMultimediaObjectAssignedToSeries('MmObject 4', $series_lhazar);

        $mm1->addPersonWithRole($person_ned, $role_lord);
        $mm1->addPersonWithRole($person_mark, $role_lord);
        $mm1->addPersonWithRole($person_benjen, $role_lord);
        $mm1->addPersonWithRole($person_ned, $role_ranger);
        $mm2->addPersonWithRole($person_ned, $role_lord);
        $mm2->addPersonWithRole($person_ned, $role_ranger);
        $mm2->addPersonWithRole($person_benjen, $role_ranger);
        $mm2->addPersonWithRole($person_mark, $role_hand);
        $mm3->addPersonWithRole($person_ned, $role_lord);
        $mm3->addPersonWithRole($person_benjen, $role_ranger);
        $mm4->addPersonWithRole($person_mark, $role_ranger);
        $mm4->addPersonWithRole($person_ned, $role_hand);
        $mm4->addPersonWithRole($person_catherin, $role_lord);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();

        $peopleLord = $this->repo->findPeopleWithRoleCode($role_lord->getCod());
        static::assertCount(4, $peopleLord);
        $peopleRanger = $this->repo->findPeopleWithRoleCode($role_ranger->getCod());
        static::assertCount(3, $peopleRanger);
        $peopleHand = $this->repo->findPeopleWithRoleCode($role_hand->getCod());
        static::assertCount(2, $peopleHand);

        $person = $this->repo->findPeopleWithRoleCodeAndId($role_ranger->getCod(), $person_mark->getEmail());
        static::assertCount(1, $person);
        $person = $this->repo->findPeopleWithRoleCodeAndId($role_lord->getCod(), $person_ned->getEmail());
        static::assertCount(2, $person);
    }

    public function testFindRelatedMultimediaObjects(): void
    {
        $tagUNESCO = new Tag();
        $tagUNESCO->setCod('UNESCO');
        $tag1 = new Tag();
        $tag1->setCod('tag1');
        $tag2 = new Tag();
        $tag2->setCod('tag2');
        $tag3 = new Tag();
        $tag3->setCod('tag3');

        $tag1->setParent($tagUNESCO);
        $tag2->setParent($tagUNESCO);
        $tag3->setParent($tagUNESCO);

        $this->dm->persist($tag1);
        $this->dm->persist($tag2);
        $this->dm->persist($tag3);
        $this->dm->persist($tagUNESCO);
        $this->dm->flush();

        $series1 = $this->createSeries('Series 1');
        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $series2 = $this->createSeries('Series 2');
        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm23 = $this->factoryService->createMultimediaObject($series2);

        $series3 = $this->createSeries('Series 3');
        $mm31 = $this->factoryService->createMultimediaObject($series3);
        $mm32 = $this->factoryService->createMultimediaObject($series3);
        $mm33 = $this->factoryService->createMultimediaObject($series3);

        $mm11->addTag($tag1);
        $mm12->addTag($tag2);
        $mm13->addTag($tag1);
        $mm21->addTag($tag3);
        $mm22->addTag($tag1);
        $mm23->addTag($tag1);
        $mm31->addTag($tag1);
        $mm32->addTag($tag3);
        $mm33->addTag($tag3);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->persist($mm23);
        $this->dm->persist($mm31);
        $this->dm->persist($mm32);
        $this->dm->persist($mm33);
        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        static::assertCount(0, $this->repo->findRelatedMultimediaObjects($mm33));
    }

    public function testCount(): void
    {
        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');
        $series3 = $this->createSeries('Series 3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $mm1 = $this->createMultimediaObjectAssignedToSeries('mm1', $series1);
        $mm1->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('mm2', $series1);
        $mm2->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm3 = $this->createMultimediaObjectAssignedToSeries('mm3', $series2);
        $mm3->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm4 = $this->createMultimediaObjectAssignedToSeries('mm4', $series3);
        $mm4->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $this->dm->flush();

        static::assertEquals(4, $this->repo->count());
        static::assertEquals(492, $this->repo->countDuration());
    }

    public function testEmbeddedPerson(): void
    {
        $person = $this->createPerson('Person');
        $embeddedPerson = new EmbeddedPerson($person);

        $name = 'EmbeddedPerson';
        $web = 'http://www.url.com';
        $phone = '+34986123456';
        $honorific = 'honorific';
        $firm = 'firm';
        $post = 'post';
        $bio = 'biography';
        $locale = 'en';

        $embeddedPerson->setName($name);
        $embeddedPerson->setWeb($web);
        $embeddedPerson->setPhone($phone);
        $embeddedPerson->setHonorific($honorific);
        $embeddedPerson->setFirm($firm);
        $embeddedPerson->setPost($post);
        $embeddedPerson->setBio($bio);
        $embeddedPerson->setLocale($locale);

        $this->dm->persist($embeddedPerson);
        $this->dm->flush();

        $hname = $embeddedPerson->getHonorific().' '.$embeddedPerson->getName();
        $other = $embeddedPerson->getPost().' '.$embeddedPerson->getFirm().' '.$embeddedPerson->getBio();
        $info = $embeddedPerson->getPost().', '.$embeddedPerson->getFirm().', '.$embeddedPerson->getBio();

        static::assertEquals($name, $embeddedPerson->getName());
        static::assertEquals($web, $embeddedPerson->getWeb());
        static::assertEquals($phone, $embeddedPerson->getPhone());
        static::assertEquals($honorific, $embeddedPerson->getHonorific());
        static::assertEquals($firm, $embeddedPerson->getFirm());
        static::assertEquals($post, $embeddedPerson->getPost());
        static::assertEquals($bio, $embeddedPerson->getBio());
        static::assertEquals($locale, $embeddedPerson->getLocale());
        static::assertEquals($hname, $embeddedPerson->getHName());
        static::assertEquals($other, $embeddedPerson->getOther());
        static::assertEquals($info, $embeddedPerson->getInfo());

        $localeEs = 'es';
        $honorificEs = 'honores';
        $firmEs = 'firma';
        $postEs = 'publicacion';
        $bioEs = 'biografia';

        $honorificI18n = [$locale => $honorific, $localeEs => $honorificEs];
        $firmI18n = [$locale => $firm, $localeEs => $firmEs];
        $postI18n = [$locale => $post, $localeEs => $postEs];
        $bioI18n = [$locale => $bio, $localeEs => $bioEs];

        $embeddedPerson->setI18nHonorific($honorificI18n);
        $embeddedPerson->setI18nFirm($firmI18n);
        $embeddedPerson->setI18nPost($postI18n);
        $embeddedPerson->setI18nBio($bioI18n);

        $this->dm->persist($embeddedPerson);
        $this->dm->flush();

        static::assertEquals($honorificI18n, $embeddedPerson->getI18nHonorific());
        static::assertEquals($firmI18n, $embeddedPerson->getI18nFirm());
        static::assertEquals($postI18n, $embeddedPerson->getI18nPost());
        static::assertEquals($bioI18n, $embeddedPerson->getI18nBio());

        $honorific = '';
        $firm = '';
        $post = '';
        $bio = '';

        $embeddedPerson->setHonorific($honorific);
        $embeddedPerson->setFirm($firm);
        $embeddedPerson->setPost($post);
        $embeddedPerson->setBio($bio);

        $this->dm->persist($embeddedPerson);
        $this->dm->flush();

        static::assertEquals($honorific, $embeddedPerson->getHonorific());
        static::assertEquals($firm, $embeddedPerson->getFirm());
        static::assertEquals($post, $embeddedPerson->getPost());
        static::assertEquals($bio, $embeddedPerson->getBio());
    }

    public function testEmbeddedRole(): void
    {
        $role = $this->createRole('Role');
        $embeddedRole = new EmbeddedRole($role);

        $name = 'EmbeddedRole';
        $cod = 'EmbeddedRole';
        $xml = '<xml content and definition of this/>';
        $text = 'Black then white are all i see in my infancy.';
        $locale = 'en';

        $embeddedRole->setName($name);
        $embeddedRole->setCod($cod);
        $embeddedRole->setXml($xml);
        $embeddedRole->setText($text);
        $embeddedRole->setLocale($locale);

        $this->dm->persist($embeddedRole);
        $this->dm->flush();

        static::assertEquals($name, $embeddedRole->getName());
        static::assertEquals($cod, $embeddedRole->getCod());
        static::assertEquals($xml, $embeddedRole->getXml());
        static::assertEquals($text, $embeddedRole->getText());
        static::assertEquals($locale, $embeddedRole->getLocale());

        $localeEs = 'es';
        $nameEs = 'RolEmbebido';
        $textEs = 'Blano y negro es todo lo que vi en mi infancia.';

        $nameI18n = [$locale => $name, $localeEs => $nameEs];
        $textI18n = [$locale => $text, $localeEs => $textEs];

        $embeddedRole->setI18nName($nameI18n);
        $embeddedRole->setI18nText($textI18n);

        $this->dm->persist($embeddedRole);
        $this->dm->flush();

        static::assertEquals($nameI18n, $embeddedRole->getI18nName());
        static::assertEquals($textI18n, $embeddedRole->getI18nText());

        $name = '';
        $text = '';

        $embeddedRole->setName($name);
        $embeddedRole->setText($text);

        $this->dm->persist($embeddedRole);
        $this->dm->flush();

        static::assertEquals($name, $embeddedRole->getName());
        static::assertEquals($text, $embeddedRole->getText());

        $person_ned = $this->createPerson('Ned');
        $embeddedRole->addPerson($person_ned);

        static::assertTrue($embeddedRole->containsPerson($person_ned));

        $person_benjen = $this->createPerson('Benjen');
        $embeddedRole->addPerson($person_benjen);
        $person_mark = $this->createPerson('Mark');
        $embeddedRole->addPerson($person_mark);
        $person_cris = $this->createPerson('Cris');

        $this->dm->persist($embeddedRole);
        $this->dm->flush();

        $people1 = [$person_ned, $person_benjen, $person_mark];
        $people2 = [$person_ned, $person_benjen, $person_mark, $person_cris];
        $people3 = [$person_cris];

        static::assertTrue($embeddedRole->containsAllPeople($people1));
        static::assertFalse($embeddedRole->containsAllPeople($people2));
        static::assertFalse($embeddedRole->containsAnyPerson($people1));
        static::assertTrue($embeddedRole->containsAnyPerson($people3));
        static::assertNull($embeddedRole->getEmbeddedPerson($person_cris));
    }

    public function testEmbeddedTag(): void
    {
        $tag = new Tag();
        $tag->setCod('tag');

        $tag1 = new Tag();
        $tag1->setCod('embeddedTag');

        $this->dm->persist($tag);
        $this->dm->persist($tag1);

        $this->dm->flush();

        $embeddedTag = new EmbeddedTag($tag);
        $embeddedTag->setCod('embeddedTag');

        $this->dm->persist($embeddedTag);
        $this->dm->flush();

        static::assertTrue($embeddedTag->isDescendantOf($tag));
        static::assertFalse($embeddedTag->isDescendantOf($tag1));
    }

    public function testFindByTagCod(): void
    {
        $tag = new Tag();
        $tag->setCod('tag');

        $this->dm->persist($tag);
        $this->dm->flush();

        $series = $this->createSeries('Series');
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $sort = ['public_date' => -1];
        static::assertCount(0, $this->repo->findByTagCod($tag, $sort));

        $addedTags = $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        $multimediaObjects = $this->repo->findByTagCod($tag, $sort);
        static::assertCount(1, $multimediaObjects);
//        $this->assertTrue(in_array($multimediaObject, $multimediaObjects));
    }

    public function testFindAllByTag(): void
    {
        $tag = new Tag();
        $tag->setCod('tag');

        $this->dm->persist($tag);
        $this->dm->flush();

        $series = $this->createSeries('Series');
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $sort = ['public_date' => -1];
        static::assertCount(0, $this->repo->findAllByTag($tag, $sort));

        $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());

        $prototype = $this->repo->findPrototype($series);
        $this->tagService->addTagToMultimediaObject($prototype, $tag->getId());

        $multimediaObjects = $this->repo->findAllByTag($tag, $sort);
        static::assertCount(2, $multimediaObjects);
        static::assertContains($prototype, $multimediaObjects);

        $this->tagService->removeTagFromMultimediaObject($multimediaObject, $tag->getId());

        $multimediaObjects = $this->repo->findAllByTag($tag, $sort);

        static::assertCount(1, $multimediaObjects);
        static::assertContains($prototype, $multimediaObjects);
        static::assertNotContains($multimediaObject, $multimediaObjects);
    }

    public function testMultimediaObjectGroups(): void
    {
        static::assertCount(0, $this->groupRepo->findAll());

        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        static::assertCount(1, $this->groupRepo->findAll());

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        static::assertCount(2, $this->groupRepo->findAll());

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(25);
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setTitle('test');
        $multimediaObject->addGroup($group1);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        static::assertTrue($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertCount(1, $multimediaObject->getGroups());

        $multimediaObject->addGroup($group2);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        static::assertTrue($multimediaObject->containsGroup($group1));
        static::assertTrue($multimediaObject->containsGroup($group2));
        static::assertCount(2, $multimediaObject->getGroups());

        $multimediaObject->removeGroup($group1);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertTrue($multimediaObject->containsGroup($group2));
        static::assertCount(1, $multimediaObject->getGroups());

        static::assertCount(2, $this->groupRepo->findAll());
    }

    public function testFindSeriesFieldByPersonIdAndRoleCodOrGroups(): void
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $key3 = 'Group3';
        $name3 = 'Group 3';
        $group3 = $this->createGroup($key3, $name3);

        $user1 = new User();
        $user1->setEmail('user1@mail.com');
        $user1->setUsername('user1');
        $user2 = new User();
        $user2->setEmail('user2@mail.com');
        $user2->setUsername('user2');
        $user2->addGroup($group1);
        $user3 = new User();
        $user3->setEmail('user3@mail.com');
        $user3->setUsername('user3');
        $user3->addGroup($group2);
        $user4 = new User();
        $user4->setEmail('user4@mail.com');
        $user4->setUsername('user4');
        $user4->addGroup($group3);
        $user5 = new User();
        $user5->setEmail('user5@mail.com');
        $user5->setUsername('user5');
        $user5->addGroup($group1);
        $user5->addGroup($group2);
        $user6 = new User();
        $user6->setEmail('user6@mail.com');
        $user6->setUsername('user6');
        $user6->addGroup($group1);
        $user6->addGroup($group3);
        $user7 = new User();
        $user7->setEmail('user7@mail.com');
        $user7->setUsername('user7');
        $user7->addGroup($group2);
        $user7->addGroup($group3);
        $user8 = new User();
        $user8->setEmail('user8@mail.com');
        $user8->setUsername('user8');
        $user8->addGroup($group1);
        $user8->addGroup($group2);
        $user8->addGroup($group3);
        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);
        $this->dm->persist($user4);
        $this->dm->persist($user5);
        $this->dm->persist($user6);
        $this->dm->persist($user7);
        $this->dm->persist($user8);
        $this->dm->flush();

        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');
        $series3 = $this->createSeries('Series 3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $person1 = $this->createPerson('Person 1');
        $person2 = $this->createPerson('Person 2');

        $role1 = $this->createRole('Role1');
        $role2 = $this->createRole('Role2');
        $role3 = $this->createRole('Role3');

        $mm1 = $this->createMultimediaObjectAssignedToSeries('MmObject 1', $series1);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series2);
        $mm3 = $this->createMultimediaObjectAssignedToSeries('MmObject 3', $series1);
        $mm4 = $this->createMultimediaObjectAssignedToSeries('MmObject 4', $series3);

        $mm1->addPersonWithRole($person1, $role1);
        $mm2->addPersonWithRole($person2, $role2);
        $mm3->addPersonWithRole($person1, $role1);
        $mm4->addPersonWithRole($person1, $role3);

        $mm1->addGroup($group1);
        $mm1->addGroup($group3);
        $mm2->addGroup($group3);
        $mm3->addGroup($group2);
        $mm4->addGroup($group2);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();

        // Test find by person and role or groups
        $seriesPerson1Role1User1 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user1->getGroups());
        $seriesPerson1Role2User1 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user1->getGroups());
        $seriesPerson1Role3User1 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user1->getGroups());
        $seriesPerson2Role1User1 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user1->getGroups());
        $seriesPerson2Role2User1 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user1->getGroups());
        $seriesPerson2Role3User1 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user1->getGroups());

        static::assertCount(1, $seriesPerson1Role1User1);
        static::assertCount(0, $seriesPerson1Role2User1);
        static::assertCount(1, $seriesPerson1Role3User1);
        static::assertCount(0, $seriesPerson2Role1User1);
        static::assertCount(1, $seriesPerson2Role2User1);
        static::assertCount(0, $seriesPerson2Role3User1);

        $seriesPerson1Role1User1 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role1User1);
        static::assertContains($series1->getId(), $seriesPerson1Role1User1);
        static::assertNotContains($series2->getId(), $seriesPerson1Role1User1);
        static::assertNotContains($series3->getId(), $seriesPerson1Role1User1);
        $seriesPerson1Role2User1 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role2User1);
        static::assertNotContains($series1->getId(), $seriesPerson1Role2User1);
        static::assertNotContains($series2->getId(), $seriesPerson1Role2User1);
        static::assertNotContains($series3->getId(), $seriesPerson1Role2User1);
        $seriesPerson1Role3User1 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role3User1);
        static::assertNotContains($series1->getId(), $seriesPerson1Role3User1);
        static::assertNotContains($series2->getId(), $seriesPerson1Role3User1);
        static::assertContains($series3->getId(), $seriesPerson1Role3User1);
        $seriesPerson2Role1User1 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role1User1);
        static::assertNotContains($series1->getId(), $seriesPerson2Role1User1);
        static::assertNotContains($series2->getId(), $seriesPerson2Role1User1);
        static::assertNotContains($series3->getId(), $seriesPerson2Role1User1);
        $seriesPerson2Role2User1 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role2User1);
        static::assertNotContains($series1->getId(), $seriesPerson2Role2User1);
        static::assertContains($series2->getId(), $seriesPerson2Role2User1);
        static::assertNotContains($series3->getId(), $seriesPerson2Role2User1);
        $seriesPerson2Role3User1 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role3User1);
        static::assertNotContains($series1->getId(), $seriesPerson2Role3User1);
        static::assertNotContains($series2->getId(), $seriesPerson2Role3User1);
        static::assertNotContains($series3->getId(), $seriesPerson2Role3User1);

        $seriesPerson1Role1User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user2->getGroups());
        $seriesPerson1Role2User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user2->getGroups());
        $seriesPerson1Role3User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user2->getGroups());
        $seriesPerson2Role1User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user2->getGroups());
        $seriesPerson2Role2User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user2->getGroups());
        $seriesPerson2Role3User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user2->getGroups());

        static::assertCount(1, $seriesPerson1Role1User2);
        static::assertCount(1, $seriesPerson1Role2User2);
        static::assertCount(2, $seriesPerson1Role3User2);
        static::assertCount(1, $seriesPerson2Role1User2);
        static::assertCount(2, $seriesPerson2Role2User2);
        static::assertCount(1, $seriesPerson2Role3User2);

        $seriesPerson1Role1User2 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role1User2);
        static::assertContains($series1->getId(), $seriesPerson1Role1User2);
        static::assertNotContains($series2->getId(), $seriesPerson1Role1User2);
        static::assertNotContains($series3->getId(), $seriesPerson1Role1User2);
        $seriesPerson1Role2User2 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role2User2);
        static::assertContains($series1->getId(), $seriesPerson1Role2User2);
        static::assertNotContains($series2->getId(), $seriesPerson1Role2User2);
        static::assertNotContains($series3->getId(), $seriesPerson1Role2User2);
        $seriesPerson1Role3User2 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role3User2);
        static::assertContains($series1->getId(), $seriesPerson1Role3User2);
        static::assertNotContains($series2->getId(), $seriesPerson1Role3User2);
        static::assertContains($series3->getId(), $seriesPerson1Role3User2);
        $seriesPerson2Role1User2 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role1User2);
        static::assertContains($series1->getId(), $seriesPerson2Role1User2);
        static::assertNotContains($series2->getId(), $seriesPerson2Role1User2);
        static::assertNotContains($series3->getId(), $seriesPerson2Role1User2);
        $seriesPerson2Role2User2 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role2User2);
        static::assertContains($series1->getId(), $seriesPerson2Role2User2);
        static::assertContains($series2->getId(), $seriesPerson2Role2User2);
        static::assertNotContains($series3->getId(), $seriesPerson2Role2User2);
        $seriesPerson2Role3User2 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role3User2);
        static::assertContains($series1->getId(), $seriesPerson2Role3User2);
        static::assertNotContains($series2->getId(), $seriesPerson2Role3User2);
        static::assertNotContains($series3->getId(), $seriesPerson2Role3User2);

        $seriesPerson1Role1User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user3->getGroups());
        $seriesPerson1Role2User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user3->getGroups());
        $seriesPerson1Role3User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user3->getGroups());
        $seriesPerson2Role1User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user3->getGroups());
        $seriesPerson2Role2User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user3->getGroups());
        $seriesPerson2Role3User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user3->getGroups());

        static::assertCount(2, $seriesPerson1Role1User3);
        static::assertCount(2, $seriesPerson1Role2User3);
        static::assertCount(2, $seriesPerson1Role3User3);
        static::assertCount(2, $seriesPerson2Role1User3);
        static::assertCount(3, $seriesPerson2Role2User3);
        static::assertCount(2, $seriesPerson2Role3User3);

        $seriesPerson1Role1User3 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role1User3);
        static::assertContains($series1->getId(), $seriesPerson1Role1User3);
        static::assertNotContains($series2->getId(), $seriesPerson1Role1User3);
        static::assertContains($series3->getId(), $seriesPerson1Role1User3);
        $seriesPerson1Role2User3 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role2User3);
        static::assertContains($series1->getId(), $seriesPerson1Role2User3);
        static::assertNotContains($series2->getId(), $seriesPerson1Role2User3);
        static::assertContains($series3->getId(), $seriesPerson1Role2User3);
        $seriesPerson1Role3User3 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role3User3);
        static::assertContains($series1->getId(), $seriesPerson1Role3User3);
        static::assertNotContains($series2->getId(), $seriesPerson1Role3User3);
        static::assertContains($series3->getId(), $seriesPerson1Role3User3);
        $seriesPerson2Role1User3 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role1User3);
        static::assertContains($series1->getId(), $seriesPerson2Role1User3);
        static::assertNotContains($series2->getId(), $seriesPerson2Role1User3);
        static::assertContains($series3->getId(), $seriesPerson2Role1User3);
        $seriesPerson2Role2User3 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role2User3);
        static::assertContains($series1->getId(), $seriesPerson2Role2User3);
        static::assertContains($series2->getId(), $seriesPerson2Role2User3);
        static::assertContains($series3->getId(), $seriesPerson2Role2User3);
        $seriesPerson2Role3User3 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role3User3);
        static::assertContains($series1->getId(), $seriesPerson2Role3User3);
        static::assertNotContains($series2->getId(), $seriesPerson2Role3User3);
        static::assertContains($series3->getId(), $seriesPerson2Role3User3);

        $seriesPerson1Role1User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user4->getGroups());
        $seriesPerson1Role2User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user4->getGroups());
        $seriesPerson1Role3User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user4->getGroups());
        $seriesPerson2Role1User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user4->getGroups());
        $seriesPerson2Role2User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user4->getGroups());
        $seriesPerson2Role3User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user4->getGroups());

        static::assertCount(2, $seriesPerson1Role1User4);
        static::assertCount(2, $seriesPerson1Role2User4);
        static::assertCount(3, $seriesPerson1Role3User4);
        static::assertCount(2, $seriesPerson2Role1User4);
        static::assertCount(2, $seriesPerson2Role2User4);
        static::assertCount(2, $seriesPerson2Role3User4);

        $seriesPerson1Role1User4 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role1User4);
        static::assertContains($series1->getId(), $seriesPerson1Role1User4);
        static::assertContains($series2->getId(), $seriesPerson1Role1User4);
        static::assertNotContains($series3->getId(), $seriesPerson1Role1User4);
        $seriesPerson1Role2User4 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role2User4);
        static::assertContains($series1->getId(), $seriesPerson1Role2User4);
        static::assertContains($series2->getId(), $seriesPerson1Role2User4);
        static::assertNotContains($series3->getId(), $seriesPerson1Role2User4);
        $seriesPerson1Role3User4 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role3User4);
        static::assertContains($series1->getId(), $seriesPerson1Role3User4);
        static::assertContains($series2->getId(), $seriesPerson1Role3User4);
        static::assertContains($series3->getId(), $seriesPerson1Role3User4);
        $seriesPerson2Role1User4 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role1User4);
        static::assertContains($series1->getId(), $seriesPerson2Role1User4);
        static::assertContains($series2->getId(), $seriesPerson2Role1User4);
        static::assertNotContains($series3->getId(), $seriesPerson2Role1User4);
        $seriesPerson2Role2User4 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role2User4);
        static::assertContains($series1->getId(), $seriesPerson2Role2User4);
        static::assertContains($series2->getId(), $seriesPerson2Role2User4);
        static::assertNotContains($series3->getId(), $seriesPerson2Role2User4);
        $seriesPerson2Role3User4 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role3User4);
        static::assertContains($series1->getId(), $seriesPerson2Role3User4);
        static::assertContains($series2->getId(), $seriesPerson2Role3User4);
        static::assertNotContains($series3->getId(), $seriesPerson2Role3User4);

        $seriesPerson1Role1User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user5->getGroups());
        $seriesPerson1Role2User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user5->getGroups());
        $seriesPerson1Role3User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user5->getGroups());
        $seriesPerson2Role1User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user5->getGroups());
        $seriesPerson2Role2User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user5->getGroups());
        $seriesPerson2Role3User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user5->getGroups());

        static::assertCount(2, $seriesPerson1Role1User5);
        static::assertCount(2, $seriesPerson1Role2User5);
        static::assertCount(2, $seriesPerson1Role3User5);
        static::assertCount(2, $seriesPerson2Role1User5);
        static::assertCount(3, $seriesPerson2Role2User5);
        static::assertCount(2, $seriesPerson2Role3User5);

        $seriesPerson1Role1User5 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role1User5);
        static::assertContains($series1->getId(), $seriesPerson1Role1User5);
        static::assertNotContains($series2->getId(), $seriesPerson1Role1User5);
        static::assertContains($series3->getId(), $seriesPerson1Role1User5);
        $seriesPerson1Role2User5 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role2User5);
        static::assertContains($series1->getId(), $seriesPerson1Role2User5);
        static::assertNotContains($series2->getId(), $seriesPerson1Role2User5);
        static::assertContains($series3->getId(), $seriesPerson1Role2User5);
        $seriesPerson1Role3User5 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role3User5);
        static::assertContains($series1->getId(), $seriesPerson1Role3User5);
        static::assertNotContains($series2->getId(), $seriesPerson1Role3User5);
        static::assertContains($series3->getId(), $seriesPerson1Role3User5);
        $seriesPerson2Role1User5 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role1User5);
        static::assertContains($series1->getId(), $seriesPerson2Role1User5);
        static::assertNotContains($series2->getId(), $seriesPerson2Role1User5);
        static::assertContains($series3->getId(), $seriesPerson2Role1User5);
        $seriesPerson2Role2User5 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role2User5);
        static::assertContains($series1->getId(), $seriesPerson2Role2User5);
        static::assertContains($series2->getId(), $seriesPerson2Role2User5);
        static::assertContains($series3->getId(), $seriesPerson2Role2User5);
        $seriesPerson2Role3User5 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role3User5);
        static::assertContains($series1->getId(), $seriesPerson2Role3User5);
        static::assertNotContains($series2->getId(), $seriesPerson2Role3User5);
        static::assertContains($series3->getId(), $seriesPerson2Role3User5);

        $seriesPerson1Role1User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user6->getGroups());
        $seriesPerson1Role2User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user6->getGroups());
        $seriesPerson1Role3User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user6->getGroups());
        $seriesPerson2Role1User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user6->getGroups());
        $seriesPerson2Role2User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user6->getGroups());
        $seriesPerson2Role3User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user6->getGroups());

        static::assertCount(2, $seriesPerson1Role1User6);
        static::assertCount(2, $seriesPerson1Role2User6);
        static::assertCount(3, $seriesPerson1Role3User6);
        static::assertCount(2, $seriesPerson2Role1User6);
        static::assertCount(2, $seriesPerson2Role2User6);
        static::assertCount(2, $seriesPerson2Role3User6);

        $seriesPerson1Role1User6 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role1User6);
        static::assertContains($series1->getId(), $seriesPerson1Role1User6);
        static::assertContains($series2->getId(), $seriesPerson1Role1User6);
        static::assertNotContains($series3->getId(), $seriesPerson1Role1User6);
        $seriesPerson1Role2User6 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role2User6);
        static::assertContains($series1->getId(), $seriesPerson1Role2User6);
        static::assertContains($series2->getId(), $seriesPerson1Role2User6);
        static::assertNotContains($series3->getId(), $seriesPerson1Role2User6);
        $seriesPerson1Role3User6 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role3User6);
        static::assertContains($series1->getId(), $seriesPerson1Role3User6);
        static::assertContains($series2->getId(), $seriesPerson1Role3User6);
        static::assertContains($series3->getId(), $seriesPerson1Role3User6);
        $seriesPerson2Role1User6 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role1User6);
        static::assertContains($series1->getId(), $seriesPerson2Role1User6);
        static::assertContains($series2->getId(), $seriesPerson2Role1User6);
        static::assertNotContains($series3->getId(), $seriesPerson2Role1User6);
        $seriesPerson2Role2User6 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role2User6);
        static::assertContains($series1->getId(), $seriesPerson2Role2User6);
        static::assertContains($series2->getId(), $seriesPerson2Role2User6);
        static::assertNotContains($series3->getId(), $seriesPerson2Role2User6);
        $seriesPerson2Role3User6 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role3User6);
        static::assertContains($series1->getId(), $seriesPerson2Role3User6);
        static::assertContains($series2->getId(), $seriesPerson2Role3User6);
        static::assertNotContains($series3->getId(), $seriesPerson2Role3User6);

        $seriesPerson1Role1User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user7->getGroups());
        $seriesPerson1Role2User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user7->getGroups());
        $seriesPerson1Role3User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user7->getGroups());
        $seriesPerson2Role1User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user7->getGroups());
        $seriesPerson2Role2User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user7->getGroups());
        $seriesPerson2Role3User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user7->getGroups());

        static::assertCount(3, $seriesPerson1Role1User7);
        static::assertCount(3, $seriesPerson1Role2User7);
        static::assertCount(3, $seriesPerson1Role3User7);
        static::assertCount(3, $seriesPerson2Role1User7);
        static::assertCount(3, $seriesPerson2Role2User7);
        static::assertCount(3, $seriesPerson2Role3User7);

        $seriesPerson1Role1User7 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role1User7);
        static::assertContains($series1->getId(), $seriesPerson1Role1User7);
        static::assertContains($series2->getId(), $seriesPerson1Role1User7);
        static::assertContains($series3->getId(), $seriesPerson1Role1User7);
        $seriesPerson1Role2User7 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role2User7);
        static::assertContains($series1->getId(), $seriesPerson1Role2User7);
        static::assertContains($series2->getId(), $seriesPerson1Role2User7);
        static::assertContains($series3->getId(), $seriesPerson1Role2User7);
        $seriesPerson1Role3User7 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role3User7);
        static::assertContains($series1->getId(), $seriesPerson1Role3User7);
        static::assertContains($series2->getId(), $seriesPerson1Role3User7);
        static::assertContains($series3->getId(), $seriesPerson1Role3User7);
        $seriesPerson2Role1User7 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role1User7);
        static::assertContains($series1->getId(), $seriesPerson2Role1User7);
        static::assertContains($series2->getId(), $seriesPerson2Role1User7);
        static::assertContains($series3->getId(), $seriesPerson2Role1User7);
        $seriesPerson2Role2User7 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role2User7);
        static::assertContains($series1->getId(), $seriesPerson2Role2User7);
        static::assertContains($series2->getId(), $seriesPerson2Role2User7);
        static::assertContains($series3->getId(), $seriesPerson2Role2User7);
        $seriesPerson2Role3User7 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role3User7);
        static::assertContains($series1->getId(), $seriesPerson2Role3User7);
        static::assertContains($series2->getId(), $seriesPerson2Role3User7);
        static::assertContains($series3->getId(), $seriesPerson2Role3User7);

        $seriesPerson1Role1User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user8->getGroups());
        $seriesPerson1Role2User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user8->getGroups());
        $seriesPerson1Role3User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user8->getGroups());
        $seriesPerson2Role1User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user8->getGroups());
        $seriesPerson2Role2User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user8->getGroups());
        $seriesPerson2Role3User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user8->getGroups());

        static::assertCount(3, $seriesPerson1Role1User8);
        static::assertCount(3, $seriesPerson1Role2User8);
        static::assertCount(3, $seriesPerson1Role3User8);
        static::assertCount(3, $seriesPerson2Role1User8);
        static::assertCount(3, $seriesPerson2Role2User8);
        static::assertCount(3, $seriesPerson2Role3User8);

        $seriesPerson1Role1User8 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role1User8);
        static::assertContains($series1->getId(), $seriesPerson1Role1User8);
        static::assertContains($series2->getId(), $seriesPerson1Role1User8);
        static::assertContains($series3->getId(), $seriesPerson1Role1User8);
        $seriesPerson1Role2User8 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role2User8);
        static::assertContains($series1->getId(), $seriesPerson1Role2User8);
        static::assertContains($series2->getId(), $seriesPerson1Role2User8);
        static::assertContains($series3->getId(), $seriesPerson1Role2User8);
        $seriesPerson1Role3User8 = array_map(static function ($a) { return (string) $a; }, $seriesPerson1Role3User8);
        static::assertContains($series1->getId(), $seriesPerson1Role3User8);
        static::assertContains($series2->getId(), $seriesPerson1Role3User8);
        static::assertContains($series3->getId(), $seriesPerson1Role3User8);
        $seriesPerson2Role1User8 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role1User8);
        static::assertContains($series1->getId(), $seriesPerson2Role1User8);
        static::assertContains($series2->getId(), $seriesPerson2Role1User8);
        static::assertContains($series3->getId(), $seriesPerson2Role1User8);
        $seriesPerson2Role2User8 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role2User8);
        static::assertContains($series1->getId(), $seriesPerson2Role2User8);
        static::assertContains($series2->getId(), $seriesPerson2Role2User8);
        static::assertContains($series3->getId(), $seriesPerson2Role2User8);
        $seriesPerson2Role3User8 = array_map(static function ($a) { return (string) $a; }, $seriesPerson2Role3User8);
        static::assertContains($series1->getId(), $seriesPerson2Role3User8);
        static::assertContains($series2->getId(), $seriesPerson2Role3User8);
        static::assertContains($series3->getId(), $seriesPerson2Role3User8);
    }

    public function testFindWithGroup(): void
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $series = $this->createSeries('Series');

        $this->dm->persist($series);
        $this->dm->flush();

        $mm1 = $this->createMultimediaObjectAssignedToSeries('MmObject 1', $series);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series);

        $mm1->addGroup($group1);
        $mm1->addGroup($group2);
        $mm2->addGroup($group2);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertCount(2, $mm1->getGroups());
        static::assertCount(1, $mm2->getGroups());
        static::assertContains($group1, $mm1->getGroups());
        static::assertContains($group2, $mm1->getGroups());
        static::assertNotContains($group1, $mm2->getGroups());
        static::assertContains($group2, $mm2->getGroups());

        $mmsGroup1 = $this->repo->findWithGroup($group1);
        $mmsGroup2 = $this->repo->findWithGroup($group2);

        static::assertCount(1, $mmsGroup1);
        static::assertCount(2, $mmsGroup2);
        static::assertContains($mm1, $mmsGroup1);
        static::assertNotContains($mm2, $mmsGroup1);
        static::assertContains($mm1, $mmsGroup2);
        static::assertContains($mm2, $mmsGroup2);
    }

    public function testFindWithGroupInEmbeddedBroadcast(): void
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $series = $this->createSeries('Series');

        $this->dm->persist($series);
        $this->dm->flush();

        $mm1 = $this->createMultimediaObjectAssignedToSeries('MmObject 1', $series);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series);

        $type = EmbeddedBroadcast::TYPE_GROUPS;
        $name = EmbeddedBroadcast::NAME_GROUPS;

        $embeddedBroadcast1 = new EmbeddedBroadcast();
        $embeddedBroadcast1->setType($type);
        $embeddedBroadcast1->setName($name);
        $embeddedBroadcast1->addGroup($group1);
        $embeddedBroadcast1->addGroup($group2);

        $embeddedBroadcast2 = new EmbeddedBroadcast();
        $embeddedBroadcast2->setType($type);
        $embeddedBroadcast2->setName($name);
        $embeddedBroadcast2->addGroup($group2);

        $mm1->setEmbeddedBroadcast($embeddedBroadcast1);
        $mm2->setEmbeddedBroadcast($embeddedBroadcast2);
        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertCount(2, $embeddedBroadcast1->getGroups());
        static::assertCount(1, $embeddedBroadcast2->getGroups());
        static::assertContains($group1, $embeddedBroadcast1->getGroups());
        static::assertContains($group2, $embeddedBroadcast1->getGroups());
        static::assertNotContains($group1, $embeddedBroadcast2->getGroups());
        static::assertContains($group2, $embeddedBroadcast2->getGroups());

        $mmsGroup1 = $this->repo->findWithGroupInEmbeddedBroadcast($group1);
        $mmsGroup2 = $this->repo->findWithGroupInEmbeddedBroadcast($group2);

        static::assertCount(1, $mmsGroup1);
        static::assertCount(2, $mmsGroup2);
        static::assertContains($mm1, $mmsGroup1);
        static::assertNotContains($mm2, $mmsGroup1);
        static::assertContains($mm1, $mmsGroup2);
        static::assertContains($mm2, $mmsGroup2);
    }

    public function testCountWithGroup(): void
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $series = $this->createSeries('Series');

        $this->dm->persist($series);
        $this->dm->flush();

        $mm1 = $this->createMultimediaObjectAssignedToSeries('MmObject 1', $series);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series);

        $mm1->addGroup($group1);
        $mm1->addGroup($group2);
        $mm2->addGroup($group2);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertEquals(1, $this->repo->countWithGroup($group1));
        static::assertEquals(2, $this->repo->countWithGroup($group2));
    }

    public function testCountWithGroupInEmbeddedBroadcast(): void
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $series = $this->createSeries('Series');

        $this->dm->persist($series);
        $this->dm->flush();

        $mm1 = $this->createMultimediaObjectAssignedToSeries('MmObject 1', $series);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series);

        $type = EmbeddedBroadcast::TYPE_GROUPS;
        $name = EmbeddedBroadcast::NAME_GROUPS;

        $embeddedBroadcast1 = new EmbeddedBroadcast();
        $embeddedBroadcast1->setType($type);
        $embeddedBroadcast1->setName($name);
        $embeddedBroadcast1->addGroup($group1);
        $embeddedBroadcast1->addGroup($group2);

        $embeddedBroadcast2 = new EmbeddedBroadcast();
        $embeddedBroadcast2->setType($type);
        $embeddedBroadcast2->setName($name);
        $embeddedBroadcast2->addGroup($group2);

        $mm1->setEmbeddedBroadcast($embeddedBroadcast1);
        $mm2->setEmbeddedBroadcast($embeddedBroadcast2);
        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertEquals(1, $this->repo->countWithGroupInEmbeddedBroadcast($group1));
        static::assertEquals(2, $this->repo->countWithGroupInEmbeddedBroadcast($group2));
    }

    public function testEmbeddedBroadcast(): void
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $mm = new MultimediaObject();
        $mm->setNumericalID(2);
        $mm->setTitle('test');
        $this->dm->persist($mm);
        $this->dm->flush();

        $type = EmbeddedBroadcast::TYPE_PASSWORD;
        $name = EmbeddedBroadcast::NAME_PASSWORD;
        $password = '123456';

        $embeddedBroadcast = new EmbeddedBroadcast();
        $embeddedBroadcast->setType($type);
        $embeddedBroadcast->setName($name);
        $embeddedBroadcast->setPassword($password);
        $embeddedBroadcast->addGroup($group1);
        $embeddedBroadcast->addGroup($group2);

        $mm->setEmbeddedBroadcast($embeddedBroadcast);
        $this->dm->persist($mm);
        $this->dm->flush();

        $multimediaObject = $this->repo->find($mm->getId());
        $embBroadcast = $multimediaObject->getEmbeddedBroadcast();

        static::assertEquals($type, $embBroadcast->getType());
        static::assertEquals($name, $embBroadcast->getName());
        static::assertEquals($password, $embBroadcast->getPassword());
        static::assertTrue($embBroadcast->containsGroup($group1));
        static::assertTrue($embBroadcast->containsGroup($group2));
        static::assertEquals($name, $embBroadcast->__toString());
        static::assertTrue($embBroadcast->isPasswordValid());

        $type2 = EmbeddedBroadcast::TYPE_GROUPS;
        $name2 = EmbeddedBroadcast::NAME_GROUPS;
        $embBroadcast->setType($type2);
        $embBroadcast->setName($name2);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $multimediaObject = $this->repo->find($mm->getId());
        $embBroadcast = $multimediaObject->getEmbeddedBroadcast();

        static::assertEquals($type2, $embBroadcast->getType());
        static::assertEquals($name2, $embBroadcast->getName());
        static::assertEquals($name2.': '.$group1->getName().', '.$group2->getName(), $embBroadcast->__toString());

        $embBroadcast->removeGroup($group2);
        $this->dm->persist($mm);
        $this->dm->flush();

        $mmObj = $this->repo->find($multimediaObject->getId());
        $embBroad = $mmObj->getEmbeddedBroadcast();

        static::assertTrue($embBroad->containsGroup($group1));
        static::assertFalse($embBroad->containsGroup($group2));
    }

    public function testFindByEmbeddedBroadcast(): void
    {
        $mm1 = new MultimediaObject();
        $mm1->setNumericalID(3);
        $mm1->setTitle('test2');
        $this->dm->persist($mm1);
        $this->dm->flush();

        $mm2 = new MultimediaObject();
        $mm2->setNumericalID(4);
        $mm2->setTitle('test1');
        $this->dm->persist($mm2);
        $this->dm->flush();

        $type1 = EmbeddedBroadcast::TYPE_PASSWORD;
        $name1 = EmbeddedBroadcast::NAME_PASSWORD;
        $password1 = '123456';

        $embeddedBroadcast1 = new EmbeddedBroadcast();
        $embeddedBroadcast1->setType($type1);
        $embeddedBroadcast1->setName($name1);
        $embeddedBroadcast1->setPassword($password1);

        $type2 = EmbeddedBroadcast::TYPE_PUBLIC;
        $name2 = EmbeddedBroadcast::NAME_PUBLIC;
        $password2 = '123456';

        $embeddedBroadcast2 = new EmbeddedBroadcast();
        $embeddedBroadcast2->setType($type2);
        $embeddedBroadcast2->setName($name2);
        $embeddedBroadcast2->setPassword($password2);

        $mm1->setEmbeddedBroadcast($embeddedBroadcast1);
        $mm2->setEmbeddedBroadcast($embeddedBroadcast2);
        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertCount(1, $this->repo->findByEmbeddedBroadcast($mm1->getEmbeddedBroadcast()));
        static::assertCount(1, $this->repo->findByEmbeddedBroadcast($mm2->getEmbeddedBroadcast()));
        static::assertCount(1, $this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_PASSWORD));
        static::assertCount(1, $this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_PUBLIC));
        static::assertCount(0, $this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_LOGIN));
        static::assertCount(0, $this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_GROUPS));

        $series1 = new Series();
        $series1->setNumericalID(1);
        $series1->setTitle('series1');
        $series2 = new Series();
        $series2->setNumericalID(2);
        $series2->setTitle('series2');
        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $mm1->setSeries($series1);
        $mm2->setSeries($series2);
        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $passwordSeriesField = $this->repo->findSeriesFieldByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_PASSWORD);
        $publicSeriesField = $this->repo->findSeriesFieldByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_PUBLIC);
        $loginSeriesField = $this->repo->findSeriesFieldByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_LOGIN);
        $groupsSeriesField = $this->repo->findSeriesFieldByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_GROUPS);
        static::assertCount(1, $passwordSeriesField);
        static::assertCount(1, $publicSeriesField);
        static::assertCount(0, $loginSeriesField);
        static::assertCount(0, $groupsSeriesField);

        $passwordSeriesField = array_map(static function ($a) { return (string) $a; }, $passwordSeriesField);
        $publicSeriesField = array_map(static function ($a) { return (string) $a; }, $publicSeriesField);
        $loginSeriesField = array_map(static function ($a) { return (string) $a; }, $loginSeriesField);
        $groupsSeriesField = array_map(static function ($a) { return (string) $a; }, $groupsSeriesField);

        static::assertContains($series1->getId(), $passwordSeriesField);
        static::assertNotContains($series1->getId(), $publicSeriesField);
        static::assertNotContains($series1->getId(), $loginSeriesField);
        static::assertNotContains($series1->getId(), $groupsSeriesField);

        static::assertNotContains($series2->getId(), $passwordSeriesField);
        static::assertContains($series2->getId(), $publicSeriesField);
        static::assertNotContains($series2->getId(), $loginSeriesField);
        static::assertNotContains($series2->getId(), $groupsSeriesField);

        $group1 = new Group();
        $group1->setKey('group1');
        $group1->setName('Group 1');
        $group2 = new Group();
        $group2->setKey('group2');
        $group2->setName('Group 2');
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();
        $embeddedBroadcast1->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $embeddedBroadcast1->setName(EmbeddedBroadcast::NAME_GROUPS);
        $embeddedBroadcast1->addGroup($group1);
        $this->dm->persist($mm1);
        $this->dm->flush();

        $groups1 = [$group1->getId()];
        $groups2 = [$group2->getId()];
        $groups12 = [$group1->getId(), $group2->getId()];

        $seriesGroups1 = $this->repo->findSeriesFieldByEmbeddedBroadcastTypeAndGroups(EmbeddedBroadcast::TYPE_GROUPS, $groups1);
        $seriesGroups2 = $this->repo->findSeriesFieldByEmbeddedBroadcastTypeAndGroups(EmbeddedBroadcast::TYPE_GROUPS, $groups2);
        $seriesGroups12 = $this->repo->findSeriesFieldByEmbeddedBroadcastTypeAndGroups(EmbeddedBroadcast::TYPE_GROUPS, $groups12);
        static::assertCount(1, $seriesGroups1);
        static::assertCount(0, $seriesGroups2);
        static::assertCount(0, $seriesGroups12);

        $seriesGroups1 = array_map(static function ($a) { return (string) $a; }, $seriesGroups1);
        $seriesGroups2 = array_map(static function ($a) { return (string) $a; }, $seriesGroups2);
        $seriesGroups12 = array_map(static function ($a) { return (string) $a; }, $seriesGroups12);
        static::assertContains($series1->getId(), $seriesGroups1);
        static::assertNotContains($series2->getId(), $seriesGroups1);
        static::assertNotContains($series1->getId(), $seriesGroups2);
        static::assertNotContains($series2->getId(), $seriesGroups2);
        static::assertNotContains($series1->getId(), $seriesGroups12);
        static::assertNotContains($series2->getId(), $seriesGroups12);
    }

    public function testCountInSeriesWithPrototype(): void
    {
        $series1 = new Series();
        $series1->setNumericalID(1);
        $series2 = new Series();
        $series2->setNumericalID(2);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $mm11 = new MultimediaObject();
        $mm11->setNumericalID(11);
        $mm12 = new MultimediaObject();
        $mm12->setNumericalID(12);
        $mm13 = new MultimediaObject();
        $mm13->setNumericalID(13);

        $mm21 = new MultimediaObject();
        $mm21->setNumericalID(21);
        $mm22 = new MultimediaObject();
        $mm22->setNumericalID(22);
        $mm23 = new MultimediaObject();
        $mm23->setNumericalID(23);
        $mm24 = new MultimediaObject();
        $mm24->setNumericalID(24);

        $mm11->setSeries($series1);
        $mm12->setSeries($series1);
        $mm13->setSeries($series1);

        $mm21->setSeries($series2);
        $mm22->setSeries($series2);
        $mm23->setSeries($series2);
        $mm24->setSeries($series2);

        $mm11->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm12->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm13->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $mm21->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm22->setStatus(MultimediaObject::STATUS_BLOCKED);
        $mm23->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        $mm24->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->persist($mm23);
        $this->dm->persist($mm24);
        $this->dm->flush();

        static::assertEquals(3, $this->repo->countInSeriesWithPrototype($series1));
        static::assertEquals(4, $this->repo->countInSeriesWithPrototype($series2));
    }

    public function testCountInSeriesWithEmbeddedBroadcast(): void
    {
        $series1 = new Series();
        $series1->setNumericalID(1);
        $series2 = new Series();
        $series2->setNumericalID(2);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $typePassword = EmbeddedBroadcast::TYPE_PASSWORD;
        $namePassword = EmbeddedBroadcast::NAME_PASSWORD;

        $typePublic = EmbeddedBroadcast::TYPE_PUBLIC;
        $namePublic = EmbeddedBroadcast::NAME_PUBLIC;

        $typeLogin = EmbeddedBroadcast::TYPE_LOGIN;
        $nameLogin = EmbeddedBroadcast::NAME_LOGIN;

        $typeGroups = EmbeddedBroadcast::TYPE_GROUPS;
        $nameGroups = EmbeddedBroadcast::NAME_GROUPS;

        $password1 = 'password1';
        $password2 = 'password2';

        $embeddedBroadcast11 = new EmbeddedBroadcast();
        $embeddedBroadcast11->setType($typePassword);
        $embeddedBroadcast11->setName($namePassword);
        $embeddedBroadcast11->setPassword($password1);
        $embeddedBroadcast11->addGroup($group1);
        $embeddedBroadcast11->addGroup($group2);

        $embeddedBroadcast12 = new EmbeddedBroadcast();
        $embeddedBroadcast12->setType($typePassword);
        $embeddedBroadcast12->setName($namePassword);
        $embeddedBroadcast12->setPassword($password1);
        $embeddedBroadcast12->addGroup($group1);

        $embeddedBroadcast13 = new EmbeddedBroadcast();
        $embeddedBroadcast13->setType($typePassword);
        $embeddedBroadcast13->setName($namePassword);
        $embeddedBroadcast13->setPassword($password2);
        $embeddedBroadcast13->addGroup($group1);
        $embeddedBroadcast13->addGroup($group2);

        $embeddedBroadcast14 = new EmbeddedBroadcast();
        $embeddedBroadcast14->setType($typePublic);
        $embeddedBroadcast14->setName($namePublic);
        $embeddedBroadcast14->setPassword($password1);
        $embeddedBroadcast14->addGroup($group1);
        $embeddedBroadcast14->addGroup($group2);

        $embeddedBroadcast21 = new EmbeddedBroadcast();
        $embeddedBroadcast21->setType($typeLogin);
        $embeddedBroadcast21->setName($nameLogin);
        $embeddedBroadcast21->setPassword($password1);
        $embeddedBroadcast21->addGroup($group1);
        $embeddedBroadcast21->addGroup($group2);

        $embeddedBroadcast22 = new EmbeddedBroadcast();
        $embeddedBroadcast22->setType($typeGroups);
        $embeddedBroadcast22->setName($nameGroups);
        $embeddedBroadcast22->setPassword($password2);
        $embeddedBroadcast22->addGroup($group1);
        $embeddedBroadcast22->addGroup($group2);

        $embeddedBroadcast23 = new EmbeddedBroadcast();
        $embeddedBroadcast23->setType($typeGroups);
        $embeddedBroadcast23->setName($nameGroups);
        $embeddedBroadcast23->setPassword($password2);
        $embeddedBroadcast23->addGroup($group2);

        $embeddedBroadcast24 = new EmbeddedBroadcast();
        $embeddedBroadcast24->setType($typeGroups);
        $embeddedBroadcast24->setName($nameGroups);
        $embeddedBroadcast24->setPassword($password1);
        $embeddedBroadcast24->addGroup($group1);
        $embeddedBroadcast24->addGroup($group2);

        $mm11 = new MultimediaObject();
        $mm11->setNumericalID(11);
        $mm12 = new MultimediaObject();
        $mm12->setNumericalID(12);
        $mm13 = new MultimediaObject();
        $mm13->setNumericalID(13);
        $mm14 = new MultimediaObject();
        $mm14->setNumericalID(14);

        $mm21 = new MultimediaObject();
        $mm21->setNumericalID(21);
        $mm22 = new MultimediaObject();
        $mm22->setNumericalID(22);
        $mm23 = new MultimediaObject();
        $mm23->setNumericalID(23);
        $mm24 = new MultimediaObject();
        $mm24->setNumericalID(24);

        $mm11->setSeries($series1);
        $mm12->setSeries($series1);
        $mm13->setSeries($series1);
        $mm14->setSeries($series1);

        $mm21->setSeries($series2);
        $mm22->setSeries($series2);
        $mm23->setSeries($series2);
        $mm24->setSeries($series2);

        $mm11->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm12->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        $mm13->setStatus(MultimediaObject::STATUS_BLOCKED);

        $mm21->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm22->setStatus(MultimediaObject::STATUS_BLOCKED);
        $mm23->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        $mm24->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $mm11->setEmbeddedBroadcast($embeddedBroadcast11);
        $mm12->setEmbeddedBroadcast($embeddedBroadcast12);
        $mm13->setEmbeddedBroadcast($embeddedBroadcast13);
        $mm14->setEmbeddedBroadcast($embeddedBroadcast14);

        $mm21->setEmbeddedBroadcast($embeddedBroadcast21);
        $mm22->setEmbeddedBroadcast($embeddedBroadcast22);
        $mm23->setEmbeddedBroadcast($embeddedBroadcast23);
        $mm24->setEmbeddedBroadcast($embeddedBroadcast24);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm14);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->persist($mm23);
        $this->dm->persist($mm24);
        $this->dm->flush();

        $groups1 = [new ObjectId($group1->getId()), new ObjectId($group2->getId())];
        $groups2 = [new ObjectId($group2->getId()), new ObjectId($group1->getId())];
        $groups3 = [new ObjectId($group2->getId())];
        $groups4 = [new ObjectId($group1->getId())];
        $groups5 = [];

        static::assertEquals(1, $this->repo->countInSeriesWithEmbeddedBroadcastType($series1, $typePublic));
        static::assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastType($series2, $typePublic));
        static::assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastType($series1, $typeLogin));
        static::assertEquals(1, $this->repo->countInSeriesWithEmbeddedBroadcastType($series2, $typeLogin));

        static::assertEquals(2, $this->repo->countInSeriesWithEmbeddedBroadcastPassword($series1, $typePassword, $password1));
        static::assertEquals(1, $this->repo->countInSeriesWithEmbeddedBroadcastPassword($series1, $typePassword, $password2));
        static::assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastPassword($series2, $typePassword, $password1));
        static::assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastPassword($series2, $typePassword, $password2));

        static::assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series1, $typeGroups, $groups1));
        static::assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series1, $typeGroups, $groups2));
        static::assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series1, $typeGroups, $groups3));
        static::assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series1, $typeGroups, $groups4));
        static::assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series1, $typeGroups, $groups5));

        static::assertEquals(2, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series2, $typeGroups, $groups1));
        static::assertEquals(2, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series2, $typeGroups, $groups2));
        static::assertEquals(1, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series2, $typeGroups, $groups3));
        static::assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series2, $typeGroups, $groups4));
        static::assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series2, $typeGroups, $groups5));
    }

    private function createPerson($name): PersonInterface
    {
        $email = $name.'@mail.es';
        $web = 'http://www.url.com';
        $phone = '+34986123456';
        $honorific = 'honorific';
        $firm = 'firm';
        $post = 'post';
        $bio = 'Biografía extensa de la persona';

        $person = new Person();
        $person->setName($name);
        $person->setEmail($email);
        $person->setWeb($web);
        $person->setPhone($phone);
        $person->setHonorific($honorific);
        $person->setFirm($firm);
        $person->setPost($post);
        $person->setBio($bio);

        $this->dm->persist($person);
        $this->dm->flush();

        return $person;
    }

    private function createRole($name): RoleInterface
    {
        $cod = $name; // string (20)
        $rank = strlen($name); // Quick and dirty way to keep it unique
        $xml = '<xml content and definition of this/>';
        $display = true;
        $text = 'Black then white are all i see in my infancy.';

        $role = new Role();
        $role->setCod($cod);
        $role->setRank($rank);
        $role->setXml($xml);
        $role->setDisplay($display); // true by default
        $role->setName($name);
        $role->setText($text);
        $role->increaseNumberPeopleInMultimediaObject();

        $this->dm->persist($role);
        $this->dm->flush();

        return $role;
    }

    private function createMultimediaObjectAssignedToSeries($title, Series $series)
    {
        $rank = 1;
        $status = MultimediaObject::STATUS_NEW;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle = 'Subtitle';
        $description = 'Description';
        $duration = 123;

        $mm = $this->factoryService->createMultimediaObject($series);

        $mm->setStatus($status);
        $mm->setRecordDate($record_date);
        $mm->setPublicDate($public_date);
        $mm->setTitle($title);
        $mm->setSubtitle($subtitle);
        $mm->setDescription($description);
        $mm->setDuration($duration);

        $this->dm->persist($mm);
        $this->dm->persist($series);
        $this->dm->flush();

        return $mm;
    }

    private function createSeries($title)
    {
        $subtitle = 'subtitle';
        $description = 'description';
        $test_date = new \DateTime('now');

        $series = $this->factoryService->createSeries();

        $series->setTitle($title);
        $series->setSubtitle($subtitle);
        $series->setDescription($description);
        $series->setPublicDate($test_date);

        $this->dm->persist($series);
        $this->dm->flush();

        return $series;
    }

    private function createSeriesType($name): SeriesType
    {
        $description = 'description';
        $series_type = new SeriesType();

        $series_type->setName($name);
        $series_type->setDescription($description);

        $this->dm->persist($series_type);
        $this->dm->flush();

        return $series_type;
    }

    private function createGroup($key = 'Group1', $name = 'Group 1'): Group
    {
        $group = new Group();

        $group->setKey($key);
        $group->setName($name);

        $this->dm->persist($group);
        $this->dm->flush();

        return $group;
    }
}
