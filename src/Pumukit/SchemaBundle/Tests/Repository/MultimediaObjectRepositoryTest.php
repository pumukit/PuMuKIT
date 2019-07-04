<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\EmbeddedPerson;
use Pumukit\SchemaBundle\Document\EmbeddedRole;
use Pumukit\SchemaBundle\Document\EmbeddedTag;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;

class MultimediaObjectRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $qb;
    private $factoryService;
    private $mmsPicService;
    private $tagService;
    private $groupRepo;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository(MultimediaObject::class);
        $this->factoryService = static::$kernel->getContainer()
            ->get('pumukitschema.factory');
        $this->mmsPicService = static::$kernel->getContainer()
            ->get('pumukitschema.mmspic');
        $this->tagService = static::$kernel->getContainer()
            ->get('pumukitschema.tag');
        $this->groupRepo = $this->dm
            ->getRepository(Group::class);

        //DELETE DATABASE
        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([]);
        $this->dm->getDocumentCollection(Role::class)
            ->remove([]);
        $this->dm->getDocumentCollection(Person::class)
            ->remove([]);
        $this->dm->getDocumentCollection(Series::class)
            ->remove([]);
        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
            ->remove([]);
        $this->dm->getDocumentCollection(Broadcast::class)
            ->remove([]);
        $this->dm->getDocumentCollection(Tag::class)
            ->remove([]);
        $this->dm->getDocumentCollection(Group::class)
            ->remove([]);
        $this->dm->getDocumentCollection(User::class)
            ->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->factoryService = null;
        $this->mmsPicService = null;
        $this->tagService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testRepository()
    {
        //$rank = 1;
        $status = MultimediaObject::STATUS_PUBLISHED;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $title = 'titulo cualquiera';
        $subtitle = 'Subtitle paragraph';
        $description = 'Description text';
        $duration = 300;
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);

        $mmobj = new MultimediaObject();
        //$mmobj->setRank($rank);
        $mmobj->setStatus($status);
        $mmobj->setRecordDate($record_date);
        $mmobj->setPublicDate($public_date);
        $mmobj->setTitle($title);
        $mmobj->setSubtitle($subtitle);
        $mmobj->setDescription($description);
        $mmobj->setDuration($duration);
        $mmobj->setBroadcast($broadcast);

        $this->dm->persist($mmobj);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findAll()));

        $this->assertEquals($broadcast, $mmobj->getBroadcast());

        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);
        $mmobj->setBroadcast($broadcast);
        $this->dm->persist($mmobj);
        $this->dm->flush();

        $this->assertEquals($broadcast, $mmobj->getBroadcast());

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

        $this->assertEquals(5, count($mmobj->getFilteredTracksWithTags()));
        $this->assertEquals(3, count($mmobj->getFilteredTracksWithTags(['master'])));
        $this->assertEquals(1, count($mmobj->getFilteredTracksWithTags(['master'], ['mosca', 'old'])));
        $this->assertEquals(0, count($mmobj->getFilteredTracksWithTags([], ['mosca', 'old'], ['master'])));
        $this->assertEquals(3, count($mmobj->getFilteredTracksWithTags([], [], ['flv'])));
        $this->assertEquals(0, count($mmobj->getFilteredTracksWithTags([], [], ['flv', 'master'])));
        $this->assertEquals(5, count($mmobj->getFilteredTracksWithTags([], [], [], ['flv', 'master'])));
        $this->assertEquals(1, count($mmobj->getFilteredTracksWithTags(['mosca', 'old'], [], [], ['old'])));
        $this->assertEquals(0, count($mmobj->getFilteredTracksWithTags(['track6'])));

        $this->assertEquals($t3, $mmobj->getFilteredTrackWithTags());
        $this->assertEquals($t3, $mmobj->getFilteredTrackWithTags(['master']));
        $this->assertEquals($t2, $mmobj->getFilteredTrackWithTags(['master'], ['mosca', 'old']));
        $this->assertEquals(null, $mmobj->getFilteredTrackWithTags([], ['mosca', 'old'], ['master']));
        $this->assertEquals($t3, $mmobj->getFilteredTrackWithTags([], [], ['flv']));
        $this->assertEquals(null, $mmobj->getFilteredTrackWithTags([], [], ['flv', 'master']));
        $this->assertEquals($t3, $mmobj->getFilteredTrackWithTags([], [], [], ['flv', 'master']));
        $this->assertEquals($t3, $mmobj->getFilteredTrackWithTags(['mosca', 'old'], [], [], ['old']));
        $this->assertEquals($t1, $mmobj->getFilteredTrackWithTags([], [], [], ['master', 'mosca']));
        $this->assertEquals(null, $mmobj->getFilteredTrackWithTags(['track6']));
    }

    public function testCreateMultimediaObjectAndFindByCriteria()
    {
        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

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
        $mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series_wall);
        $mm3 = $this->createMultimediaObjectAssignedToSeries('MmObject 3', $series_main);
        $mm4 = $this->createMultimediaObjectAssignedToSeries('MmObject 4', $series_lhazar);

        $mm1->addPersonWithRole($person_ned, $role_lord);
        $mm2->addPersonWithRole($person_benjen, $role_ranger);
        $mm3->addPersonWithRole($person_ned, $role_lord);
        $mm3->addPersonWithRole($person_benjen, $role_ranger);
        $mm4->addPersonWithRole($person_ned, $role_hand);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();
        // DB setup END.

        // Test find by person
        $mmobj_ned = $this->repo->findByPersonId($person_ned->getId());
        $this->assertEquals(3, count($mmobj_ned));

        // Test find by role cod or id
        $mmobj_lord = $this->repo->findByRoleCod($role_lord->getCod())->toArray();
        $mmobj_ranger = $this->repo->findByRoleCod($role_ranger->getCod())->toArray();
        $mmobj_hand = $this->repo->findByRoleCod($role_hand->getCod())->toArray();
        $this->assertEquals(2, count($mmobj_lord));
        $this->assertEquals(2, count($mmobj_ranger));
        $this->assertEquals(1, count($mmobj_hand));
        $this->assertTrue(in_array($mm1, $mmobj_lord));
        $this->assertFalse(in_array($mm2, $mmobj_lord));
        $this->assertTrue(in_array($mm3, $mmobj_lord));
        $this->assertFalse(in_array($mm4, $mmobj_lord));
        $this->assertFalse(in_array($mm1, $mmobj_ranger));
        $this->assertTrue(in_array($mm2, $mmobj_ranger));
        $this->assertTrue(in_array($mm3, $mmobj_ranger));
        $this->assertFalse(in_array($mm4, $mmobj_ranger));
        $this->assertFalse(in_array($mm1, $mmobj_hand));
        $this->assertFalse(in_array($mm2, $mmobj_hand));
        $this->assertFalse(in_array($mm3, $mmobj_hand));
        $this->assertTrue(in_array($mm4, $mmobj_hand));

        $mmobj_lord = $this->repo->findByRoleId($role_lord->getId())->toArray();
        $mmobj_ranger = $this->repo->findByRoleId($role_ranger->getId())->toArray();
        $mmobj_hand = $this->repo->findByRoleId($role_hand->getId())->toArray();
        $this->assertEquals(2, count($mmobj_lord));
        $this->assertEquals(2, count($mmobj_ranger));
        $this->assertEquals(1, count($mmobj_hand));
        $this->assertTrue(in_array($mm1, $mmobj_lord));
        $this->assertFalse(in_array($mm2, $mmobj_lord));
        $this->assertTrue(in_array($mm3, $mmobj_lord));
        $this->assertFalse(in_array($mm4, $mmobj_lord));
        $this->assertFalse(in_array($mm1, $mmobj_ranger));
        $this->assertTrue(in_array($mm2, $mmobj_ranger));
        $this->assertTrue(in_array($mm3, $mmobj_ranger));
        $this->assertFalse(in_array($mm4, $mmobj_ranger));
        $this->assertFalse(in_array($mm1, $mmobj_hand));
        $this->assertFalse(in_array($mm2, $mmobj_hand));
        $this->assertFalse(in_array($mm3, $mmobj_hand));
        $this->assertTrue(in_array($mm4, $mmobj_hand));

        // Test find by person and role
        $mmobj_benjen_ranger = $this->repo->findByPersonIdWithRoleCod($person_benjen->getId(), $role_ranger->getCod());
        $mmobj_ned_lord = $this->repo->findByPersonIdWithRoleCod($person_ned->getId(), $role_lord->getCod());
        $mmobj_ned_hand = $this->repo->findByPersonIdWithRoleCod($person_ned->getId(), $role_hand->getCod());
        $mmobj_benjen_lord = $this->repo->findByPersonIdWithRoleCod($person_benjen->getId(), $role_lord->getCod());
        $mmobj_ned_ranger = $this->repo->findByPersonIdWithRoleCod($person_ned->getId(), $role_ranger->getCod());
        $mmobj_benjen_hand = $this->repo->findByPersonIdWithRoleCod($person_benjen->getId(), $role_hand->getCod());

        $this->assertEquals(2, count($mmobj_benjen_ranger));
        $this->assertEquals(2, count($mmobj_ned_lord));
        $this->assertEquals(1, count($mmobj_ned_hand));

        $this->assertEquals(0, count($mmobj_benjen_lord));
        $this->assertEquals(0, count($mmobj_ned_ranger));
        $this->assertEquals(0, count($mmobj_benjen_hand));

        $seriesBenjen = $this->repo->findSeriesFieldByPersonId($person_benjen->getId());
        $seriesNed = $this->repo->findSeriesFieldByPersonId($person_ned->getId());

        $this->assertEquals(2, count($seriesBenjen));
        $this->assertTrue(in_array($series_wall->getId(), $seriesBenjen->toArray()));
        $this->assertTrue(in_array($series_main->getId(), $seriesBenjen->toArray()));

        $this->assertEquals(2, count($seriesNed));
        $this->assertTrue(in_array($series_main->getId(), $seriesNed->toArray()));
        $this->assertTrue(in_array($series_lhazar->getId(), $seriesNed->toArray()));

        $seriesBenjenRanger = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_benjen->getId(), $role_ranger->getCod());
        $seriesNedRanger = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_ned->getId(), $role_ranger->getCod());
        $seriesBenjenLord = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_benjen->getId(), $role_lord->getCod());
        $seriesNedLord = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_ned->getId(), $role_lord->getCod());
        $seriesBenjenHand = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_benjen->getId(), $role_hand->getCod());
        $seriesNedHand = $this->repo->findSeriesFieldByPersonIdAndRoleCod($person_ned->getId(), $role_hand->getCod());

        $this->assertEquals(2, count($seriesBenjenRanger));
        $this->assertTrue(in_array($series_wall->getId(), $seriesBenjenRanger->toArray()));
        $this->assertTrue(in_array($series_main->getId(), $seriesBenjenRanger->toArray()));
        $this->assertFalse(in_array($series_lhazar->getId(), $seriesBenjenRanger->toArray()));

        $this->assertEquals(0, count($seriesNedRanger));
        $this->assertFalse(in_array($series_wall->getId(), $seriesNedRanger->toArray()));
        $this->assertFalse(in_array($series_main->getId(), $seriesNedRanger->toArray()));
        $this->assertFalse(in_array($series_lhazar->getId(), $seriesNedRanger->toArray()));

        $this->assertEquals(0, count($seriesBenjenLord));
        $this->assertFalse(in_array($series_wall->getId(), $seriesBenjenLord->toArray()));
        $this->assertFalse(in_array($series_main->getId(), $seriesBenjenLord->toArray()));
        $this->assertFalse(in_array($series_lhazar->getId(), $seriesBenjenLord->toArray()));

        $this->assertEquals(1, count($seriesNedLord));
        $this->assertFalse(in_array($series_wall->getId(), $seriesNedLord->toArray()));
        $this->assertTrue(in_array($series_main->getId(), $seriesNedLord->toArray()));
        $this->assertFalse(in_array($series_lhazar->getId(), $seriesNedLord->toArray()));

        $this->assertEquals(0, count($seriesBenjenHand));
        $this->assertFalse(in_array($series_wall->getId(), $seriesBenjenHand->toArray()));
        $this->assertFalse(in_array($series_main->getId(), $seriesBenjenHand->toArray()));
        $this->assertFalse(in_array($series_lhazar->getId(), $seriesBenjenHand->toArray()));

        $this->assertEquals(1, count($seriesNedHand));
        $this->assertFalse(in_array($series_wall->getId(), $seriesNedHand->toArray()));
        $this->assertFalse(in_array($series_main->getId(), $seriesNedHand->toArray()));
        $this->assertTrue(in_array($series_lhazar->getId(), $seriesNedHand->toArray()));

        $mmobjsMainNedLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_ned->getId(), $role_lord->getCod());
        $this->assertEquals(2, count($mmobjsMainNedLord));
        $this->assertTrue(in_array($mm1, $mmobjsMainNedLord->toArray()));
        $this->assertTrue(in_array($mm3, $mmobjsMainNedLord->toArray()));

        $mmobjsMainNedRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_ned->getId(), $role_ranger->getCod());
        $this->assertEquals(0, count($mmobjsMainNedRanger));
        $this->assertFalse(in_array($mm1, $mmobjsMainNedRanger->toArray()));
        $this->assertFalse(in_array($mm3, $mmobjsMainNedRanger->toArray()));

        $mmobjsMainNedHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_ned->getId(), $role_hand->getCod());
        $this->assertEquals(0, count($mmobjsMainNedHand));
        $this->assertFalse(in_array($mm1, $mmobjsMainNedHand->toArray()));
        $this->assertFalse(in_array($mm3, $mmobjsMainNedHand->toArray()));

        $mmobjsMainBenjenLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_benjen->getId(), $role_lord->getCod());
        $this->assertEquals(0, count($mmobjsMainBenjenLord));
        $this->assertFalse(in_array($mm1, $mmobjsMainBenjenLord->toArray()));
        $this->assertFalse(in_array($mm3, $mmobjsMainBenjenLord->toArray()));

        $mmobjsMainBenjenRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_benjen->getId(), $role_ranger->getCod());
        $this->assertEquals(1, count($mmobjsMainBenjenRanger));
        $this->assertFalse(in_array($mm1, $mmobjsMainBenjenRanger->toArray()));
        $this->assertTrue(in_array($mm3, $mmobjsMainBenjenRanger->toArray()));

        $mmobjsMainBenjenHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_main, $person_benjen->getId(), $role_hand->getCod());
        $this->assertEquals(0, count($mmobjsMainBenjenHand));
        $this->assertFalse(in_array($mm1, $mmobjsMainBenjenHand->toArray()));
        $this->assertFalse(in_array($mm3, $mmobjsMainBenjenHand->toArray()));

        $mmobjsWallNedLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_ned->getId(), $role_lord->getCod());
        $this->assertEquals(0, count($mmobjsWallNedLord));
        $this->assertFalse(in_array($mm2, $mmobjsWallNedLord->toArray()));

        $mmobjsWallNedRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_ned->getId(), $role_ranger->getCod());
        $this->assertEquals(0, count($mmobjsWallNedRanger));
        $this->assertFalse(in_array($mm2, $mmobjsWallNedRanger->toArray()));

        $mmobjsWallNedHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_ned->getId(), $role_hand->getCod());
        $this->assertEquals(0, count($mmobjsWallNedHand));
        $this->assertFalse(in_array($mm2, $mmobjsWallNedHand->toArray()));

        $mmobjsWallBenjenLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_benjen->getId(), $role_lord->getCod());
        $this->assertEquals(0, count($mmobjsWallBenjenLord));
        $this->assertFalse(in_array($mm2, $mmobjsWallBenjenLord->toArray()));

        $mmobjsWallBenjenRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_benjen->getId(), $role_ranger->getCod());
        $this->assertEquals(1, count($mmobjsWallBenjenRanger));
        $this->assertTrue(in_array($mm2, $mmobjsWallBenjenRanger->toArray()));

        $mmobjsWallBenjenHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_wall, $person_benjen->getId(), $role_hand->getCod());
        $this->assertEquals(0, count($mmobjsWallBenjenHand));
        $this->assertFalse(in_array($mm2, $mmobjsWallBenjenHand->toArray()));

        $mmobjsLhazarNedLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_ned->getId(), $role_lord->getCod());
        $this->assertEquals(0, count($mmobjsLhazarNedLord));
        $this->assertFalse(in_array($mm4, $mmobjsLhazarNedLord->toArray()));

        $mmobjsLhazarNedRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_ned->getId(), $role_ranger->getCod());
        $this->assertEquals(0, count($mmobjsLhazarNedRanger));
        $this->assertFalse(in_array($mm4, $mmobjsLhazarNedRanger->toArray()));

        $mmobjsLhazarNedHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_ned->getId(), $role_hand->getCod());
        $this->assertEquals(1, count($mmobjsLhazarNedHand));
        $this->assertTrue(in_array($mm4, $mmobjsLhazarNedHand->toArray()));

        $mmobjsLhazarBenjenLord = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_benjen->getId(), $role_lord->getCod());
        $this->assertEquals(0, count($mmobjsLhazarBenjenLord));
        $this->assertFalse(in_array($mm4, $mmobjsLhazarBenjenLord->toArray()));

        $mmobjsLhazarBenjenRanger = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_benjen->getId(), $role_ranger->getCod());
        $this->assertEquals(0, count($mmobjsLhazarBenjenRanger));
        $this->assertFalse(in_array($mm4, $mmobjsLhazarBenjenRanger->toArray()));

        $mmobjsLhazarBenjenHand = $this->repo->findBySeriesAndPersonIdWithRoleCod($series_lhazar, $person_benjen->getId(), $role_hand->getCod());
        $this->assertEquals(0, count($mmobjsLhazarBenjenHand));
        $this->assertFalse(in_array($mm4, $mmobjsLhazarBenjenHand->toArray()));

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

        $this->assertEquals(3, count($mmobj_benjen_ranger_group1));
        $this->assertEquals(3, count($mmobj_ned_lord_group1));
        $this->assertEquals(4, count($mmobj_ned_hand_group1));
        $this->assertEquals(3, count($mmobj_benjen_ranger_group2));
        $this->assertEquals(3, count($mmobj_ned_lord_group2));
        $this->assertEquals(2, count($mmobj_ned_hand_group2));

        $this->assertEquals(3, count($mmobj_benjen_lord_group1));
        $this->assertEquals(3, count($mmobj_ned_ranger_group1));
        $this->assertEquals(3, count($mmobj_benjen_hand_group1));
        $this->assertEquals(2, count($mmobj_benjen_lord_group2));
        $this->assertEquals(2, count($mmobj_ned_ranger_group2));
        $this->assertEquals(2, count($mmobj_benjen_hand_group2));
    }

    public function testPeopleInMultimediaObjectCollection()
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
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($mm->containsPerson($personKate));
        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertEquals(0, count($mm->getPeople()));
        $this->assertFalse($mm->containsPersonWithAllRoles($personKate, [$roleActor, $rolePresenter, $roleDirector]));
        $this->assertFalse($mm->containsPersonWithAnyRole($personKate, [$roleActor, $rolePresenter, $roleDirector]));

        $mm->addPersonWithRole($personKate, $roleActor);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->containsPerson($personKate));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertFalse($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertFalse($mm->containsPerson($personLucy));
        $this->assertEquals(1, count($mm->getPeople()));
        $this->assertEquals($personKate->getId(), $mm->getPersonWithRole($personKate, $roleActor)->getId());

        $mm2 = new MultimediaObject();
        $this->dm->persist($mm2);
        $this->dm->flush();

        $this->assertFalse($mm2->containsPerson($personKate));
        $this->assertFalse($mm2->containsPersonWithRole($personKate, $roleActor));
        $this->assertEquals(0, count($mm2->getPeople()));

        $this->assertFalse($mm2->getPersonWithRole($personKate, $roleActor));

        $mm2->addPersonWithRole($personKate, $roleActor);
        $this->dm->persist($mm2);
        $this->dm->flush();

        $this->assertTrue($mm2->containsPerson($personKate));
        $this->assertTrue($mm2->containsPersonWithRole($personKate, $roleActor));
        $this->assertFalse($mm2->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertFalse($mm2->containsPersonWithRole($personKate, $roleDirector));
        $this->assertFalse($mm2->containsPerson($personLucy));
        $this->assertEquals(1, count($mm2->getPeople()));

        $mm->addPersonWithRole($personKate, $rolePresenter);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertEquals(1, count($mm->getPeople()));

        $mm->addPersonWithRole($personKate, $roleDirector);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertTrue($mm->containsPersonWithAllRoles($personKate, [$roleActor, $rolePresenter, $roleDirector]));
        $this->assertTrue($mm->containsPersonWithAnyRole($personKate, [$roleActor, $rolePresenter, $roleDirector]));
        $this->assertEquals(1, count($mm->getPeople()));

        $mm->addPersonWithRole($personLucy, $roleDirector);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertTrue($mm->containsPerson($personLucy));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personLucy, $roleDirector));
        $this->assertEquals(2, count($mm->getPeople()));

        $this->assertEquals(2, count($mm->getPeopleByRole(null, false)));
        $mm->getEmbeddedRole($roleDirector)->setDisplay(false);
        $this->dm->persist($mm);
        $this->dm->flush();
        $this->assertEquals(2, count($mm->getPeopleByRole(null, true)));
        $this->assertEquals(1, count($mm->getPeopleByRole(null, false)));
        $mm->getEmbeddedRole($roleDirector)->setDisplay(true);
        $this->dm->persist($mm);
        $this->dm->flush();

        $peopleDirector = $mm->getPeopleByRole($roleDirector);
        $this->assertEquals([$personKate->getId(), $personLucy->getId()],
                            [$peopleDirector[0]->getId(), $peopleDirector[1]->getId()]);

        $mm->downPersonWithRole($personKate, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleByRole($roleDirector);
        $this->assertEquals([$personLucy->getId(), $personKate->getId()],
                            [$peopleDirector[0]->getId(), $peopleDirector[1]->getId()]);

        $mm->upPersonWithRole($personKate, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleByRole($roleDirector);
        $this->assertEquals([$personKate->getId(), $personLucy->getId()],
                            [$peopleDirector[0]->getId(), $peopleDirector[1]->getId()]);

        $mm->upPersonWithRole($personLucy, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleByRole($roleDirector);
        $this->assertEquals([$personLucy->getId(), $personKate->getId()],
                            [$peopleDirector[0]->getId(), $peopleDirector[1]->getId()]);

        $mm->downPersonWithRole($personLucy, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleByRole($roleDirector);
        $this->assertEquals([$personKate->getId(), $personLucy->getId()],
                            [$peopleDirector[0]->getId(), $peopleDirector[1]->getId()]);

        $this->assertEquals(3, count($mm->getAllEmbeddedPeopleByPerson($personKate)));
        $this->assertEquals(1, count($mm->getAllEmbeddedPeopleByPerson($personLucy)));
        $this->assertEquals(1, count($mm2->getAllEmbeddedPeopleByPerson($personKate)));
        $this->assertEquals(0, count($mm2->getAllEmbeddedPeopleByPerson($personLucy)));

        $this->assertTrue($mm->removePersonWithRole($personKate, $roleActor));
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertTrue($mm->containsPerson($personLucy));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personLucy, $roleDirector));
        $this->assertEquals(2, count($mm->getPeople()));

        $this->assertTrue($mm->removePersonWithRole($personLucy, $roleDirector));
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertFalse($mm->containsPerson($personLucy));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleDirector));
        $this->assertEquals(1, count($mm->getPeople()));

        $this->assertTrue($mm->removePersonWithRole($personKate, $roleDirector));
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertFalse($mm->containsPerson($personLucy));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleDirector));
        $this->assertEquals(1, count($mm->getPeople()));

        $this->assertFalse($mm->removePersonWithRole($personKate, $roleActor));
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals(1, count($mm->getPeople()));

        $this->assertTrue($mm->removePersonWithRole($personKate, $rolePresenter));
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals(0, count($mm->getPeople()));
    }

    public function testGetAllEmbeddedRoleByPerson()
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

        $this->assertTrue(in_array($roleActor->getId(), $kateQueryRolesIds));
        $this->assertTrue(in_array($rolePresenter->getId(), $kateQueryRolesIds));
        $this->assertFalse(in_array($roleDirector->getId(), $kateQueryRolesIds));

        $lucyQueryRolesIds = [];
        foreach ($mm->getAllEmbeddedRolesByPerson($personLucy) as $embeddedRole) {
            $lucyQueryRolesIds[] = $embeddedRole->getId();
        }

        $this->assertTrue(in_array($roleActor->getId(), $lucyQueryRolesIds));
        $this->assertTrue(in_array($rolePresenter->getId(), $lucyQueryRolesIds));
        $this->assertTrue(in_array($roleDirector->getId(), $lucyQueryRolesIds));

        $peteQueryRolesIds = [];
        foreach ($mm->getAllEmbeddedRolesByPerson($personPete) as $embeddedRole) {
            $peteQueryRolesIds[] = $embeddedRole->getId();
        }

        $this->assertTrue(in_array($roleActor->getId(), $peteQueryRolesIds));
        $this->assertFalse(in_array($rolePresenter->getId(), $peteQueryRolesIds));
        $this->assertTrue(in_array($roleDirector->getId(), $peteQueryRolesIds));
    }

    public function testFindBySeries()
    {
        $this->assertEquals(0, count($this->repo->findAll()));

        $series1 = $this->createSeries('Series 1');
        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $series2 = $this->createSeries('Series 2');
        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);

        $series1 = $this->dm->find(Series::class, $series1->getId());
        $series2 = $this->dm->find(Series::class, $series2->getId());

        $this->assertEquals(4, count($this->repo->findBySeries($series1)));
        $this->assertEquals(3, count($this->repo->findBySeries($series2)));

        $this->assertEquals(3, count($this->repo->findStandardBySeries($series1)));
        $this->assertEquals(2, count($this->repo->findStandardBySeries($series2)));

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

        $this->assertEquals(2, count($this->repo->findBySeriesByTagCodAndStatus($series1, 'tag3')));
        $this->assertEquals(1, count($this->repo->findBySeriesByTagCodAndStatus($series2, 'tag1')));
        $this->assertEquals(1, count($this->repo->findBySeriesByTagCodAndStatus($series2, 'tag3')));
        $this->assertEquals(1, count($this->repo->findBySeriesByTagCodAndStatus($series1, 'tag2')));
    }

    public function testFindByBroadcast()
    {
        $broadcast1 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);
        $broadcast2 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);

        $mm1 = new MultimediaObject();
        $mm1->setTitle('mm1');
        $mm1->setBroadcast($broadcast1);

        $mm2 = new MultimediaObject();
        $mm2->setTitle('mm1');
        $mm2->setBroadcast($broadcast1);

        $mm3 = new MultimediaObject();
        $mm3->setTitle('mm1');
        $mm3->setBroadcast($broadcast2);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->flush();

        $this->assertEquals(2, count($this->repo->findByBroadcast($broadcast1)));
        $this->assertEquals(1, count($this->repo->findByBroadcast($broadcast2)));
    }

    public function testFindWithStatus()
    {
        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

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

        $this->assertEquals(1, count($this->repo->findWithStatus($series, [MultimediaObject::STATUS_PROTOTYPE])));
        $this->assertEquals(1, count($this->repo->findWithStatus($series, [MultimediaObject::STATUS_NEW])));
        $this->assertEquals(1, count($this->repo->findWithStatus($series, [MultimediaObject::STATUS_HIDDEN])));
        $this->assertEquals(1, count($this->repo->findWithStatus($series, [MultimediaObject::STATUS_BLOCKED])));
        $this->assertEquals(1, count($this->repo->findWithStatus($series, [MultimediaObject::STATUS_PUBLISHED])));
        $this->assertEquals(2, count($this->repo->findWithStatus($series, [MultimediaObject::STATUS_PROTOTYPE, MultimediaObject::STATUS_NEW])));
        $this->assertEquals(3, count($this->repo->findWithStatus($series, [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_NEW, MultimediaObject::STATUS_HIDDEN])));

        $mmArray = [$mmNew->getId() => $mmNew];
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_NEW])->toArray());
        $mmArray = [$mmHide->getId() => $mmHide];
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_HIDDEN])->toArray());
        $mmArray = [$mmBloq->getId() => $mmBloq];
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_BLOCKED])->toArray());
        $mmArray = [$mmPublished->getId() => $mmPublished];
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_PUBLISHED])->toArray());
        $mmArray = [$mmPublished->getId() => $mmPublished, $mmNew->getId() => $mmNew, $mmHide->getId() => $mmHide];
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_NEW, MultimediaObject::STATUS_HIDDEN])->toArray());
    }

    public function testFindPrototype()
    {
        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

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

        $this->assertInstanceOf(MultimediaObject::class, $this->repo->findPrototype($series));
        $this->assertNotEquals($mmNew, $this->repo->findPrototype($series));
        $this->assertNotEquals($mmHide, $this->repo->findPrototype($series));
        $this->assertNotEquals($mmBloq, $this->repo->findPrototype($series));
        $this->assertNotEquals($mmPublished, $this->repo->findPrototype($series));
    }

    public function testFindWithoutPrototype()
    {
        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

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

        $this->assertEquals(4, count($this->repo->findWithoutPrototype($series)));

        $mmArray = [
             $mmNew->getId() => $mmNew,
             $mmHide->getId() => $mmHide,
             $mmBloq->getId() => $mmBloq,
             $mmPublished->getId() => $mmPublished,
             ];
        $this->assertEquals($mmArray, $this->repo->findWithoutPrototype($series)->toArray());
    }

    public function testEmbedPicsInMultimediaObject()
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
        $mm->addPic($pic1);
        $mm->addPic($pic2);
        $mm->addPic($pic3);

        $this->dm->persist($mm);

        $this->dm->flush();

        $this->assertEquals($mm, $this->repo->findByPicId($pic1->getId()));
        $this->assertEquals(null, $this->repo->findByPicId($pic4->getId()));

        $this->assertEquals($pic1, $this->repo->find($mm->getId())->getPicById($pic1->getId()));
        $this->assertEquals($pic2, $this->repo->find($mm->getId())->getPicById($pic2->getId()));
        $this->assertEquals($pic3, $this->repo->find($mm->getId())->getPicById($pic3->getId()));
        $this->assertNull($this->repo->find($mm->getId())->getPicById(null));

        $mm->removePicById($pic2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $picsArray = [$pic1, $pic3];
        $this->assertEquals(count($picsArray), count($this->repo->find($mm->getId())->getPics()));
        $this->assertEquals($picsArray, array_values($this->repo->find($mm->getId())->getPics()->toArray()));

        $mm->upPicById($pic3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $picsArray = [$pic3, $pic1];
        $this->assertEquals($picsArray, array_values($this->repo->find($mm->getId())->getPics()->toArray()));

        $mm->downPicById($pic3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $picsArray = [$pic1, $pic3];
        $this->assertEquals($picsArray, array_values($this->repo->find($mm->getId())->getPics()->toArray()));
    }

    public function testEmbedMaterialsInMultimediaObject()
    {
        $material1 = new Material();
        $material2 = new Material();
        $material3 = new Material();

        $this->dm->persist($material1);
        $this->dm->persist($material2);
        $this->dm->persist($material3);

        $mm = new MultimediaObject();
        $mm->addMaterial($material1);
        $mm->addMaterial($material2);
        $mm->addMaterial($material3);

        $this->dm->persist($mm);

        $this->dm->flush();

        $this->assertEquals($material1, $this->repo->find($mm->getId())->getMaterialById($material1->getId()));
        $this->assertEquals($material2, $this->repo->find($mm->getId())->getMaterialById($material2->getId()));
        $this->assertEquals($material3, $this->repo->find($mm->getId())->getMaterialById($material3->getId()));
        $this->assertNull($this->repo->find($mm->getId())->getMaterialById(null));

        $mm->removeMaterialById($material2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $materialsArray = [$material1, $material3];
        $this->assertEquals(count($materialsArray), count($this->repo->find($mm->getId())->getMaterials()));
        $this->assertEquals($materialsArray, array_values($this->repo->find($mm->getId())->getMaterials()->toArray()));

        $mm->upMaterialById($material3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $materialsArray = [$material3, $material1];
        $this->assertEquals($materialsArray, array_values($this->repo->find($mm->getId())->getMaterials()->toArray()));

        $mm->downMaterialById($material3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $materialsArray = [$material1, $material3];
        $this->assertEquals($materialsArray, array_values($this->repo->find($mm->getId())->getMaterials()->toArray()));
    }

    public function testEmbedLinksInMultimediaObject()
    {
        $link1 = new Link();
        $link2 = new Link();
        $link3 = new Link();

        $this->dm->persist($link1);
        $this->dm->persist($link2);
        $this->dm->persist($link3);

        $mm = new MultimediaObject();
        $mm->addLink($link1);
        $mm->addLink($link2);
        $mm->addLink($link3);

        $this->dm->persist($mm);

        $this->dm->flush();

        $this->assertEquals($link1, $this->repo->find($mm->getId())->getLinkById($link1->getId()));
        $this->assertEquals($link2, $this->repo->find($mm->getId())->getLinkById($link2->getId()));
        $this->assertEquals($link3, $this->repo->find($mm->getId())->getLinkById($link3->getId()));
        $this->assertNull($this->repo->find($mm->getId())->getLinkById(null));

        $mm->removeLinkById($link2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $linksArray = [$link1, $link3];
        $this->assertEquals(count($linksArray), count($this->repo->find($mm->getId())->getLinks()));
        $this->assertEquals($linksArray, array_values($this->repo->find($mm->getId())->getLinks()->toArray()));

        $mm->upLinkById($link3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $linksArray = [$link3, $link1];
        $this->assertEquals($linksArray, array_values($this->repo->find($mm->getId())->getLinks()->toArray()));

        $mm->downLinkById($link3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $linksArray = [$link1, $link3];
        $this->assertEquals($linksArray, array_values($this->repo->find($mm->getId())->getLinks()->toArray()));
    }

    public function testEmbedTracksInMultimediaObject()
    {
        $track1 = new Track();
        $track2 = new Track();
        $track3 = new Track();

        $this->dm->persist($track1);
        $this->dm->persist($track2);
        $this->dm->persist($track3);

        $mm = new MultimediaObject();
        $mm->addTrack($track1);
        $mm->addTrack($track2);
        $mm->addTrack($track3);

        $this->dm->persist($mm);

        $this->dm->flush();

        $this->assertEquals($track1, $this->repo->find($mm->getId())->getTrackById($track1->getId()));
        $this->assertEquals($track2, $this->repo->find($mm->getId())->getTrackById($track2->getId()));
        $this->assertEquals($track3, $this->repo->find($mm->getId())->getTrackById($track3->getId()));
        $this->assertNull($this->repo->find($mm->getId())->getTrackById(null));

        $mm->removeTrackById($track2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $tracksArray = [$track1, $track3];
        $this->assertEquals(count($tracksArray), count($this->repo->find($mm->getId())->getTracks()));
        $this->assertEquals($tracksArray, array_values($this->repo->find($mm->getId())->getTracks()->toArray()));

        $mm->upTrackById($track3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $tracksArray = [$track3, $track1];
        $this->assertEquals($tracksArray, array_values($this->repo->find($mm->getId())->getTracks()->toArray()));

        $mm->downTrackById($track3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $tracksArray = [$track1, $track3];
        $this->assertEquals($tracksArray, array_values($this->repo->find($mm->getId())->getTracks()->toArray()));
    }

    public function testFindMultimediaObjectsWithTags()
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

        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);
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
        $this->assertEquals(7, count($this->repo->findWithTag($tag1)));
        $limit = 3;
        $this->assertEquals(3, $this->repo->findWithTag($tag1, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(3, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(3, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));
        $page = 2;
        $this->assertEquals(1, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));
        $page = 3;
        $this->assertEquals(0, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));

        // FIND WITH TAG (SORT)
        $page = 1;
        $arrayAsc = [$mm23, $mm31, $mm33];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithTag($tag1, $sortAsc, $limit, $page)->toArray()));
        $arrayDesc = [$mm23, $mm22, $mm12];
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithTag($tag1, $sortDesc, $limit, $page)->toArray()));

        $this->assertEquals(2, count($this->repo->findWithTag($tag2)));
        $limit = 1;
        $this->assertEquals(1, $this->repo->findWithTag($tag2, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(1, $this->repo->findWithTag($tag2, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithTag($tag2, $sort, $limit, $page)->count(true));

        //FIND WITH GENERAL TAG
        $this->assertEquals(6, count($this->repo->findWithGeneralTag($tag1)));
        $limit = 3;
        $this->assertEquals(3, $this->repo->findWithGeneralTag($tag1, $sort, $limit)->count(true));
        $page = 1;
        $this->assertEquals(3, $this->repo->findWithGeneralTag($tag1, $sort, $limit, $page)->count(true));
        $this->assertEquals(2, count($this->repo->findWithGeneralTag($tag2)));
        $this->assertEquals(0, count($this->repo->findWithGeneralTag($tag3)));
        //FIND WITH GENERAL TAG (SORT)
        $arrayAsc = [$mm31, $mm33, $mm34];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithGeneralTag($tag1, $sortAsc, $limit, $page)->toArray()));
        $arrayDesc = [$mm23, $mm22, $mm12];
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithGeneralTag($tag1, $sortDesc, $limit, $page)->toArray()));

        // FIND ONE WITH TAG
        $this->assertEquals($mm11, $this->repo->findOneWithTag($tag1));

        // FIND WITH ANY TAG
        $arrayTags = [$tag1, $tag2, $tag3];
        $this->assertEquals(8, $this->repo->findWithAnyTag($arrayTags)->count(true));
        $limit = 3;
        $this->assertEquals(3, $this->repo->findWithAnyTag($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(3, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(3, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));
        $page = 2;
        $this->assertEquals(2, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));

        // FIND WITH ANY TAG (SORT)
        $arrayAsc = [$mm11, $mm12, $mm21, $mm22, $mm23, $mm31, $mm33, $mm34];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAnyTag($arrayTags, $sortAsc)->toArray()));
        $limit = 3;
        $arrayAsc = [$mm11, $mm12, $mm21];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit)->toArray()));
        $page = 0;
        $arrayAsc = [$mm11, $mm12, $mm21];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit, $page)->toArray()));
        $page = 1;
        $arrayAsc = [$mm22, $mm23, $mm31];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit, $page)->toArray()));
        $page = 2;
        $arrayAsc = [$mm33, $mm34];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit, $page)->toArray()));

        $arrayDesc = [$mm34, $mm33, $mm31, $mm23, $mm22, $mm21, $mm12, $mm11];
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithAnyTag($arrayTags, $sortDesc)->toArray()));
        $limit = 5;
        $page = 0;
        $arrayDesc = [$mm34, $mm33, $mm31, $mm23, $mm22];
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithAnyTag($arrayTags, $sortDesc, $limit, $page)->toArray()));
        $page = 1;
        $arrayDesc = [$mm21, $mm12, $mm11];
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithAnyTag($arrayTags, $sortDesc, $limit, $page)->toArray()));

        // Add more tags
        $mm32->addTag($tag3);
        $this->dm->persist($mm32);
        $this->dm->flush();
        $this->assertEquals(9, $this->repo->findWithAnyTag($arrayTags)->count(true));

        $arrayTags = [$tag2, $tag3];
        $this->assertEquals(3, $this->repo->findWithAnyTag($arrayTags)->count(true));

        // FIND WITH ALL TAGS
        $mm32->addTag($tag2);

        $mm13->addTag($tag1);
        $mm13->addTag($tag2);

        $this->dm->persist($mm13);
        $this->dm->persist($mm32);
        $this->dm->flush();

        $arrayTags = [$tag1, $tag2];
        $this->assertEquals(2, $this->repo->findWithAllTags($arrayTags)->count(true));

        $mm12->addTag($tag2);
        $mm22->addTag($tag2);
        $this->dm->persist($mm12);
        $this->dm->persist($mm22);
        $this->dm->flush();

        $this->assertEquals(4, $this->repo->findWithAllTags($arrayTags)->count(true));
        $limit = 3;
        $this->assertEquals(3, $this->repo->findWithAllTags($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(3, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page)->count(true));

        $arrayTags = [$tag2, $tag3];
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags)->count(true));

        // FIND WITH ALL TAGS (SORT)
        $arrayTags = [$tag1, $tag2];
        $arrayAsc = [$mm11, $mm12, $mm13, $mm22];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAllTags($arrayTags, $sortAsc)->toArray()));
        $arrayDesc = [$mm22, $mm13, $mm12, $mm11];
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithAllTags($arrayTags, $sortDesc)->toArray()));
        $limit = 3;
        $arrayAsc = [$mm11, $mm12, $mm13];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAllTags($arrayTags, $sortAsc, $limit)->toArray()));
        $page = 1;
        $arrayAsc = [$mm22];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAllTags($arrayTags, $sortAsc, $limit, $page)->toArray()));

        $limit = 2;
        $page = 1;
        $arrayDesc = [$mm12, $mm11];
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithAllTags($arrayTags, $sortDesc, $limit, $page)->toArray()));

        // FIND ONE WITH ALL TAGS
        $arrayTags = [$tag1, $tag2];
        $this->assertEquals($mm11, $this->repo->findOneWithAllTags($arrayTags));

        // FIND WITHOUT TAG
        $this->assertEquals(9, $this->repo->findWithoutTag($tag3)->count(true));
        $limit = 4;
        $this->assertEquals(4, $this->repo->findWithoutTag($tag3, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(4, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(4, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));
        $page = 2;
        $this->assertEquals(1, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));
        $page = 3;
        $this->assertEquals(0, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));

        // FIND WITHOUT TAG (SORT)
        $arrayAsc = [$mm11, $mm12, $mm13, $mm21, $mm22, $mm23, $mm31, $mm33, $mm34];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithoutTag($tag3, $sortAsc)->toArray()));
        $limit = 6;
        $arrayAsc = [$mm11, $mm12, $mm13, $mm21, $mm22, $mm23];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithoutTag($tag3, $sortAsc, $limit)->toArray()));
        $page = 1;
        $arrayAsc = [$mm31, $mm33, $mm34];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithoutTag($tag3, $sortAsc, $limit, $page)->toArray()));

        $arrayDesc = [$mm13, $mm12, $mm11];
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithoutTag($tag3, $sortDesc, $limit, $page)->toArray()));

        // FIND ONE WITHOUT TAG
        $this->assertEquals($mm23, $this->repo->findOneWithoutTag($tag2));

        // FIND WITH ALL TAGS

        // FIND WITHOUT ALL TAGS
        $arrayTags = [$tag2, $tag3];
        $this->assertEquals(4, $this->repo->findWithoutAllTags($arrayTags)->count(true));
        $limit = 3;
        $this->assertEquals(3, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(3, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit, $page)->count(true));

        $arrayTags = [$tag1, $tag3];
        $this->assertEquals(1, $this->repo->findWithoutAllTags($arrayTags)->count(true));

        $arrayTags = [$tag1, $tag2];
        $this->assertEquals(0, $this->repo->findWithoutAllTags($arrayTags)->count(true));

        // FIND WITHOUT ALL TAGS (SORT)
        $arrayTags = [$tag2, $tag3];
        $arrayAsc = [$mm23, $mm31, $mm33, $mm34];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithoutAllTags($arrayTags, $sortAsc)->toArray()));
        $limit = 3;
        $page = 1;
        $arrayAsc = [$mm34];
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithoutAllTags($arrayTags, $sortAsc, $limit, $page)->toArray()));

        $page = 0;
        $arrayDesc = [$mm34, $mm33, $mm31];
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithoutAllTags($arrayTags, $sortDesc, $limit, $page)->toArray()));
    }

    public function testFindSeriesFieldWithTags()
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

        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);
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
        $this->assertEquals(3, count($this->repo->findSeriesFieldWithTag($tag1)));
        $this->assertEquals(1, count($this->repo->findSeriesFieldWithTag($tag3)));

        // FIND ONE SERIES FIELD WITH TAG
        $this->assertEquals($series3->getId(), $this->repo->findOneSeriesFieldWithTag($tag3));

        // FIND SERIES FIELD WITH ANY TAG
        $arrayTags = [$tag1, $tag2];
        $this->assertEquals(3, $this->repo->findSeriesFieldWithAnyTag($arrayTags)->count(true));

        $arrayTags = [$tag3];
        $this->assertEquals(1, $this->repo->findSeriesFieldWithAnyTag($arrayTags)->count(true));

        // FIND SERIES FIELD WITH ALL TAGS
        $arrayTags = [$tag1, $tag2];
        $this->assertEquals(2, $this->repo->findSeriesFieldWithAllTags($arrayTags)->count(true));

        $arrayTags = [$tag2, $tag3];
        $this->assertEquals(1, $this->repo->findSeriesFieldWithAllTags($arrayTags)->count(true));

        // FIND ONE SERIES FIELD WITH ALL TAGS
        $arrayTags = [$tag1, $tag2];
        $this->assertEquals($series1->getId(), $this->repo->findOneSeriesFieldWithAllTags($arrayTags));

        $arrayTags = [$tag2, $tag3];
        $this->assertEquals($series3->getId(), $this->repo->findOneSeriesFieldWithAllTags($arrayTags));
    }

    public function testFindDistinctPics()
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

        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);

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

        $this->assertEquals(3, count($mm11->getPics()));
        $this->assertEquals(3, count($this->repo->find($mm11->getId())->getPics()));
        $this->assertEquals(1, count($mm12->getPics()));
        $this->assertEquals(1, count($this->repo->find($mm12->getId())->getPics()));
        $this->assertEquals(1, count($mm21->getPics()));
        $this->assertEquals(1, count($this->repo->find($mm21->getId())->getPics()));

        $this->assertEquals(3, count($this->repo->findDistinctUrlPicsInSeries($series1)));

        $this->assertEquals(4, count($this->repo->findDistinctUrlPics()));

        $mm11->setPublicDate(new \DateTime('2015-01-03 15:05:16'));
        $mm12->setPublicDate(new \DateTime('2015-01-03 15:05:20'));
        $mm21->setPublicDate(new \DateTime('2015-01-03 15:05:25'));

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm21);
        $this->dm->flush();

        $arrayPics = [$pic1->getUrl(), $pic2->getUrl(), $pic3->getUrl(), $pic5->getUrl()];
        //$this->assertEquals($arrayPics, $this->repo->findDistinctUrlPics()->toArray());

        $mm11->setPublicDate(new \DateTime('2015-01-13 15:05:16'));
        $mm12->setPublicDate(new \DateTime('2015-01-23 15:05:20'));
        $mm21->setPublicDate(new \DateTime('2015-01-03 15:05:25'));

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm21);
        $this->dm->flush();

        $arrayPics = [$pic5->getUrl(), $pic1->getUrl(), $pic3->getUrl(), $pic3->getUrl()];
        //$this->assertEquals($arrayPics, $this->repo->findDistinctUrlPics()->toArray());
    }

    public function testFindOrderedBy()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);
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

        $this->assertEquals(3, $this->repo->findOrderedBy($series, $sort)->count(true));
        $this->assertEquals(3, $this->repo->findOrderedBy($series, $sortPubDateAsc)->count(true));
        $this->assertEquals(3, $this->repo->findOrderedBy($series, $sortPubDateDesc)->count(true));
        $this->assertEquals(3, $this->repo->findOrderedBy($series, $sortRecDateAsc)->count(true));
        $this->assertEquals(3, $this->repo->findOrderedBy($series, $sortRecDateDesc)->count(true));

        $arrayMms = [$mm1, $mm2, $mm3];
        $this->assertEquals($arrayMms, array_values($this->repo->findOrderedBy($series, $sortPubDateAsc)->toArray()));
        $arrayMms = [$mm3, $mm2, $mm1];
        $this->assertEquals($arrayMms, array_values($this->repo->findOrderedBy($series, $sortPubDateDesc)->toArray()));
        $arrayMms = [$mm3, $mm1, $mm2];
        $this->assertEquals($arrayMms, array_values($this->repo->findOrderedBy($series, $sortRecDateAsc)->toArray()));
        $arrayMms = [$mm2, $mm1, $mm3];
        $this->assertEquals($arrayMms, array_values($this->repo->findOrderedBy($series, $sortRecDateDesc)->toArray()));
    }

    public function testEmbeddedTagChildOfTag()
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

        $this->assertTrue($tag3->isChildOf($tag2));
        $this->assertFalse($tag3->isChildOf($tag1));
        $this->assertFalse($tag3->isChildOf($tag4));

        $this->assertTrue($tag2->isChildOf($tag1));
        $this->assertFalse($tag1->isChildOf($tag2));

        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);
        $series = $this->createSeries('Series');
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $tagAdded = $this->tagService->addTagToMultimediaObject($multimediaObject, $tag3->getId());

        $embeddedTags = $multimediaObject->getTags();
        $embeddedTag1 = $embeddedTags[2];
        $embeddedTag2 = $embeddedTags[1];
        $embeddedTag3 = $embeddedTags[0];

        $this->assertTrue($embeddedTag3->isChildOf($tag2));
        $this->assertTrue($embeddedTag2->isChildOf($tag1));

        $this->assertTrue($embeddedTag3->isChildOf($embeddedTag2));
        $this->assertTrue($embeddedTag2->isChildOf($embeddedTag1));

        $this->assertFalse($embeddedTag3->isChildOf($tag1));
        $this->assertFalse($embeddedTag3->isChildOf($embeddedTag1));

        $this->assertFalse($tag1->isChildOf($embeddedTag3));
        $this->assertFalse($embeddedTag1->isChildOf($embeddedTag3));

        $this->assertFalse($embeddedTag1->isChildOf($tag4));
        $this->assertFalse($embeddedTag2->isChildOf($tag4));
        $this->assertFalse($embeddedTag3->isChildOf($tag4));
    }

    public function testCountInSeries()
    {
        $series1 = new Series();
        $series2 = new Series();

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $mm11 = new MultimediaObject();
        $mm12 = new MultimediaObject();
        $mm13 = new MultimediaObject();

        $mm21 = new MultimediaObject();
        $mm22 = new MultimediaObject();
        $mm23 = new MultimediaObject();
        $mm24 = new MultimediaObject();

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

        $this->assertEquals(2, $this->repo->countInSeries($series1));
        $this->assertEquals(3, $this->repo->countInSeries($series2));
    }

    public function testCountPeopleWithRoleCode()
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
        $this->assertEquals(4, count($peopleLord));
        $peopleRanger = $this->repo->findPeopleWithRoleCode($role_ranger->getCod());
        $this->assertEquals(3, count($peopleRanger));
        $peopleHand = $this->repo->findPeopleWithRoleCode($role_hand->getCod());
        $this->assertEquals(2, count($peopleHand));

        $person = $this->repo->findPersonWithRoleCodeAndEmail($role_ranger->getCod(), $person_mark->getEmail());
        $this->assertEquals(1, count($person));
        $person = $this->repo->findPersonWithRoleCodeAndEmail($role_lord->getCod(), $person_ned->getEmail());
        $this->assertEquals(2, count($person));
    }

    public function testFindRelatedMultimediaObjects()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);

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

        $this->assertEquals(0, count($this->repo->findRelatedMultimediaObjects($mm33)));
    }

    public function testCount()
    {
        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');
        $series3 = $this->createSeries('Series 3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $mm1 = $this->createMultimediaObjectAssignedToSeries('mm1', $series1);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('mm2', $series1);
        $mm3 = $this->createMultimediaObjectAssignedToSeries('mm3', $series2);
        $mm4 = $this->createMultimediaObjectAssignedToSeries('mm4', $series3);

        $this->assertEquals(4, $this->repo->count());
        $this->assertEquals(492, $this->repo->countDuration());
    }

    public function testEmbeddedPerson()
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

        $this->assertEquals($name, $embeddedPerson->getName());
        $this->assertEquals($web, $embeddedPerson->getWeb());
        $this->assertEquals($phone, $embeddedPerson->getPhone());
        $this->assertEquals($honorific, $embeddedPerson->getHonorific());
        $this->assertEquals($firm, $embeddedPerson->getFirm());
        $this->assertEquals($post, $embeddedPerson->getPost());
        $this->assertEquals($bio, $embeddedPerson->getBio());
        $this->assertEquals($locale, $embeddedPerson->getLocale());
        $this->assertEquals($hname, $embeddedPerson->getHName());
        $this->assertEquals($other, $embeddedPerson->getOther());
        $this->assertEquals($info, $embeddedPerson->getInfo());

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

        $this->assertEquals($honorificI18n, $embeddedPerson->getI18nHonorific());
        $this->assertEquals($firmI18n, $embeddedPerson->getI18nFirm());
        $this->assertEquals($postI18n, $embeddedPerson->getI18nPost());
        $this->assertEquals($bioI18n, $embeddedPerson->getI18nBio());

        $honorific = null;
        $firm = null;
        $post = null;
        $bio = null;

        $embeddedPerson->setHonorific($honorific);
        $embeddedPerson->setFirm($firm);
        $embeddedPerson->setPost($post);
        $embeddedPerson->setBio($bio);

        $this->dm->persist($embeddedPerson);
        $this->dm->flush();

        $this->assertEquals($honorific, $embeddedPerson->getHonorific());
        $this->assertEquals($firm, $embeddedPerson->getFirm());
        $this->assertEquals($post, $embeddedPerson->getPost());
        $this->assertEquals($bio, $embeddedPerson->getBio());
    }

    public function testEmbeddedRole()
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

        $this->assertEquals($name, $embeddedRole->getName());
        $this->assertEquals($cod, $embeddedRole->getCod());
        $this->assertEquals($xml, $embeddedRole->getXml());
        $this->assertEquals($text, $embeddedRole->getText());
        $this->assertEquals($locale, $embeddedRole->getLocale());

        $localeEs = 'es';
        $nameEs = 'RolEmbebido';
        $textEs = 'Blano y negro es todo lo que vi en mi infancia.';

        $nameI18n = [$locale => $name, $localeEs => $nameEs];
        $textI18n = [$locale => $text, $localeEs => $textEs];

        $embeddedRole->setI18nName($nameI18n);
        $embeddedRole->setI18nText($textI18n);

        $this->dm->persist($embeddedRole);
        $this->dm->flush();

        $this->assertEquals($nameI18n, $embeddedRole->getI18nName());
        $this->assertEquals($textI18n, $embeddedRole->getI18nText());

        $name = null;
        $text = null;

        $embeddedRole->setName($name);
        $embeddedRole->setText($text);

        $this->dm->persist($embeddedRole);
        $this->dm->flush();

        $this->assertEquals($name, $embeddedRole->getName());
        $this->assertEquals($text, $embeddedRole->getText());

        $person_ned = $this->createPerson('Ned');
        $embeddedRole->addPerson($person_ned);

        $this->assertTrue($embeddedRole->containsPerson($person_ned));

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

        $this->assertTrue($embeddedRole->containsAllPeople($people1));
        $this->assertFalse($embeddedRole->containsAllPeople($people2));
        $this->assertFalse($embeddedRole->containsAnyPerson($people1));
        $this->assertTrue($embeddedRole->containsAnyPerson($people3));
        $this->assertFalse($embeddedRole->getEmbeddedPerson($person_cris));

        $role = new Role();
        //var_dump($embeddedRole->createEmbeddedPerson($role));
    }

    public function testEmbeddedTag()
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

        $this->assertTrue($embeddedTag->isDescendantOf($tag));
        $this->assertFalse($embeddedTag->isDescendantOf($tag1));
    }

    public function testFindByTagCod()
    {
        $tag = new Tag();
        $tag->setCod('tag');

        $this->dm->persist($tag);
        $this->dm->flush();

        $series = $this->createSeries('Series');
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $sort = ['public_date' => -1];
        $this->assertCount(0, $this->repo->findByTagCod($tag, $sort));

        $addedTags = $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        $multimediaObjects = $this->repo->findByTagCod($tag, $sort)->toArray();
        $this->assertCount(1, $multimediaObjects);
        $this->assertTrue(in_array($multimediaObject, $multimediaObjects));
    }

    public function testFindAllByTag()
    {
        $tag = new Tag();
        $tag->setCod('tag');

        $this->dm->persist($tag);
        $this->dm->flush();

        $series = $this->createSeries('Series');
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $sort = ['public_date' => -1];
        $this->assertCount(0, $this->repo->findAllByTag($tag, $sort));

        $addedTags = $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());

        $prototype = $this->repo->findPrototype($series);
        $addedTagsToPrototype = $this->tagService->addTagToMultimediaObject($prototype, $tag->getId());

        $multimediaObjects = $this->repo->findAllByTag($tag, $sort)->toArray();
        $this->assertCount(2, $multimediaObjects);
        $this->assertTrue(in_array($multimediaObject, $multimediaObjects));
        $this->assertTrue(in_array($prototype, $multimediaObjects));

        $removedTagsFromPrototype = $this->tagService->removeTagFromMultimediaObject($prototype, $tag->getId());

        $multimediaObjects = $this->repo->findAllByTag($tag, $sort)->toArray();
        $this->assertCount(1, $multimediaObjects);
        $this->assertTrue(in_array($multimediaObject, $multimediaObjects));
        $this->assertFalse(in_array($prototype, $multimediaObjects));
    }

    public function testMultimediaObjectGroups()
    {
        $this->assertEquals(0, count($this->groupRepo->findAll()));

        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $this->assertEquals(1, count($this->groupRepo->findAll()));

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $this->assertEquals(2, count($this->groupRepo->findAll()));

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle('test');
        $multimediaObject->addGroup($group1);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->assertTrue($multimediaObject->containsGroup($group1));
        $this->assertFalse($multimediaObject->containsGroup($group2));
        $this->assertEquals(1, $multimediaObject->getGroups()->count());

        $multimediaObject->addGroup($group2);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->assertTrue($multimediaObject->containsGroup($group1));
        $this->assertTrue($multimediaObject->containsGroup($group2));
        $this->assertEquals(2, $multimediaObject->getGroups()->count());

        $multimediaObject->removeGroup($group1);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->assertFalse($multimediaObject->containsGroup($group1));
        $this->assertTrue($multimediaObject->containsGroup($group2));
        $this->assertEquals(1, $multimediaObject->getGroups()->count());

        $this->assertEquals(2, count($this->groupRepo->findAll()));
    }

    public function testFindSeriesFieldByPersonIdAndRoleCodOrGroups()
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

        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
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

        $this->assertEquals(1, count($seriesPerson1Role1User1));
        $this->assertEquals(0, count($seriesPerson1Role2User1));
        $this->assertEquals(1, count($seriesPerson1Role3User1));
        $this->assertEquals(0, count($seriesPerson2Role1User1));
        $this->assertEquals(1, count($seriesPerson2Role2User1));
        $this->assertEquals(0, count($seriesPerson2Role3User1));

        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role1User1->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role1User1->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson1Role1User1->toArray()));
        $this->assertFalse(in_array($series1->getId(), $seriesPerson1Role2User1->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role2User1->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson1Role2User1->toArray()));
        $this->assertFalse(in_array($series1->getId(), $seriesPerson1Role3User1->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role3User1->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role3User1->toArray()));
        $this->assertFalse(in_array($series1->getId(), $seriesPerson2Role1User1->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson2Role1User1->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role1User1->toArray()));
        $this->assertFalse(in_array($series1->getId(), $seriesPerson2Role2User1->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role2User1->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role2User1->toArray()));
        $this->assertFalse(in_array($series1->getId(), $seriesPerson2Role3User1->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson2Role3User1->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role3User1->toArray()));

        $seriesPerson1Role1User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user2->getGroups());
        $seriesPerson1Role2User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user2->getGroups());
        $seriesPerson1Role3User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user2->getGroups());
        $seriesPerson2Role1User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user2->getGroups());
        $seriesPerson2Role2User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user2->getGroups());
        $seriesPerson2Role3User2 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user2->getGroups());

        $this->assertEquals(1, count($seriesPerson1Role1User2));
        $this->assertEquals(1, count($seriesPerson1Role2User2));
        $this->assertEquals(2, count($seriesPerson1Role3User2));
        $this->assertEquals(1, count($seriesPerson2Role1User2));
        $this->assertEquals(2, count($seriesPerson2Role2User2));
        $this->assertEquals(1, count($seriesPerson2Role3User2));

        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role1User2->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role1User2->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson1Role1User2->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role2User2->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role2User2->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson1Role2User2->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role3User2->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role3User2->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role3User2->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role1User2->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson2Role1User2->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role1User2->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role2User2->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role2User2->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role2User2->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role3User2->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson2Role3User2->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role3User2->toArray()));

        $seriesPerson1Role1User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user3->getGroups());
        $seriesPerson1Role2User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user3->getGroups());
        $seriesPerson1Role3User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user3->getGroups());
        $seriesPerson2Role1User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user3->getGroups());
        $seriesPerson2Role2User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user3->getGroups());
        $seriesPerson2Role3User3 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user3->getGroups());

        $this->assertEquals(2, count($seriesPerson1Role1User3));
        $this->assertEquals(2, count($seriesPerson1Role2User3));
        $this->assertEquals(2, count($seriesPerson1Role3User3));
        $this->assertEquals(2, count($seriesPerson2Role1User3));
        $this->assertEquals(3, count($seriesPerson2Role2User3));
        $this->assertEquals(2, count($seriesPerson2Role3User3));

        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role1User3->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role1User3->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role1User3->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role2User3->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role2User3->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role2User3->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role3User3->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role3User3->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role3User3->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role1User3->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson2Role1User3->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role1User3->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role2User3->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role2User3->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role2User3->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role3User3->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson2Role3User3->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role3User3->toArray()));

        $seriesPerson1Role1User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user4->getGroups());
        $seriesPerson1Role2User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user4->getGroups());
        $seriesPerson1Role3User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user4->getGroups());
        $seriesPerson2Role1User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user4->getGroups());
        $seriesPerson2Role2User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user4->getGroups());
        $seriesPerson2Role3User4 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user4->getGroups());

        $this->assertEquals(2, count($seriesPerson1Role1User4));
        $this->assertEquals(2, count($seriesPerson1Role2User4));
        $this->assertEquals(3, count($seriesPerson1Role3User4));
        $this->assertEquals(2, count($seriesPerson2Role1User4));
        $this->assertEquals(2, count($seriesPerson2Role2User4));
        $this->assertEquals(2, count($seriesPerson2Role3User4));

        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role1User4->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role1User4->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson1Role1User4->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role2User4->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role2User4->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson1Role2User4->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role3User4->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role3User4->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role3User4->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role1User4->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role1User4->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role1User4->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role2User4->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role2User4->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role2User4->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role3User4->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role3User4->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role3User4->toArray()));

        $seriesPerson1Role1User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user5->getGroups());
        $seriesPerson1Role2User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user5->getGroups());
        $seriesPerson1Role3User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user5->getGroups());
        $seriesPerson2Role1User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user5->getGroups());
        $seriesPerson2Role2User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user5->getGroups());
        $seriesPerson2Role3User5 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user5->getGroups());

        $this->assertEquals(2, count($seriesPerson1Role1User5));
        $this->assertEquals(2, count($seriesPerson1Role2User5));
        $this->assertEquals(2, count($seriesPerson1Role3User5));
        $this->assertEquals(2, count($seriesPerson2Role1User5));
        $this->assertEquals(3, count($seriesPerson2Role2User5));
        $this->assertEquals(2, count($seriesPerson2Role3User5));

        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role1User5->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role1User5->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role1User5->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role2User5->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role2User5->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role2User5->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role3User5->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson1Role3User5->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role3User5->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role1User5->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson2Role1User5->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role1User5->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role2User5->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role2User5->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role2User5->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role3User5->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesPerson2Role3User5->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role3User5->toArray()));

        $seriesPerson1Role1User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user6->getGroups());
        $seriesPerson1Role2User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user6->getGroups());
        $seriesPerson1Role3User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user6->getGroups());
        $seriesPerson2Role1User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user6->getGroups());
        $seriesPerson2Role2User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user6->getGroups());
        $seriesPerson2Role3User6 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user6->getGroups());

        $this->assertEquals(2, count($seriesPerson1Role1User6));
        $this->assertEquals(2, count($seriesPerson1Role2User6));
        $this->assertEquals(3, count($seriesPerson1Role3User6));
        $this->assertEquals(2, count($seriesPerson2Role1User6));
        $this->assertEquals(2, count($seriesPerson2Role2User6));
        $this->assertEquals(2, count($seriesPerson2Role3User6));

        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role1User6->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role1User6->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson1Role1User6->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role2User6->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role2User6->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson1Role2User6->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role3User6->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role3User6->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role3User6->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role1User6->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role1User6->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role1User6->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role2User6->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role2User6->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role2User6->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role3User6->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role3User6->toArray()));
        $this->assertFalse(in_array($series3->getId(), $seriesPerson2Role3User6->toArray()));

        $seriesPerson1Role1User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user7->getGroups());
        $seriesPerson1Role2User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user7->getGroups());
        $seriesPerson1Role3User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user7->getGroups());
        $seriesPerson2Role1User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user7->getGroups());
        $seriesPerson2Role2User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user7->getGroups());
        $seriesPerson2Role3User7 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user7->getGroups());

        $this->assertEquals(3, count($seriesPerson1Role1User7));
        $this->assertEquals(3, count($seriesPerson1Role2User7));
        $this->assertEquals(3, count($seriesPerson1Role3User7));
        $this->assertEquals(3, count($seriesPerson2Role1User7));
        $this->assertEquals(3, count($seriesPerson2Role2User7));
        $this->assertEquals(3, count($seriesPerson2Role3User7));

        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role1User7->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role1User7->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role1User7->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role2User7->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role2User7->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role2User7->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role3User7->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role3User7->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role3User7->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role1User7->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role1User7->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role1User7->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role2User7->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role2User7->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role2User7->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role3User7->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role3User7->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role3User7->toArray()));

        $seriesPerson1Role1User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role1->getCod(), $user8->getGroups());
        $seriesPerson1Role2User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role2->getCod(), $user8->getGroups());
        $seriesPerson1Role3User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person1->getId(), $role3->getCod(), $user8->getGroups());
        $seriesPerson2Role1User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role1->getCod(), $user8->getGroups());
        $seriesPerson2Role2User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role2->getCod(), $user8->getGroups());
        $seriesPerson2Role3User8 = $this->repo->findSeriesFieldByPersonIdAndRoleCodOrGroups($person2->getId(), $role3->getCod(), $user8->getGroups());

        $this->assertEquals(3, count($seriesPerson1Role1User8));
        $this->assertEquals(3, count($seriesPerson1Role2User8));
        $this->assertEquals(3, count($seriesPerson1Role3User8));
        $this->assertEquals(3, count($seriesPerson2Role1User8));
        $this->assertEquals(3, count($seriesPerson2Role2User8));
        $this->assertEquals(3, count($seriesPerson2Role3User8));

        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role1User8->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role1User8->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role1User8->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role2User8->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role2User8->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role2User8->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson1Role3User8->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson1Role3User8->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson1Role3User8->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role1User8->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role1User8->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role1User8->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role2User8->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role2User8->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role2User8->toArray()));
        $this->assertTrue(in_array($series1->getId(), $seriesPerson2Role3User8->toArray()));
        $this->assertTrue(in_array($series2->getId(), $seriesPerson2Role3User8->toArray()));
        $this->assertTrue(in_array($series3->getId(), $seriesPerson2Role3User8->toArray()));
    }

    public function testFindWithGroup()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

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

        $this->assertEquals(2, count($mm1->getGroups()));
        $this->assertEquals(1, count($mm2->getGroups()));
        $this->assertTrue(in_array($group1, $mm1->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $mm1->getGroups()->toArray()));
        $this->assertFalse(in_array($group1, $mm2->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $mm2->getGroups()->toArray()));

        $mmsGroup1 = $this->repo->findWithGroup($group1);
        $mmsGroup2 = $this->repo->findWithGroup($group2);

        $this->assertEquals(1, count($mmsGroup1));
        $this->assertEquals(2, count($mmsGroup2));
        $this->assertTrue(in_array($mm1, $mmsGroup1->toArray()));
        $this->assertFalse(in_array($mm2, $mmsGroup1->toArray()));
        $this->assertTrue(in_array($mm1, $mmsGroup2->toArray()));
        $this->assertTrue(in_array($mm2, $mmsGroup2->toArray()));
    }

    public function testFindWithGroupInEmbeddedBroadcast()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

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

        $this->assertEquals(2, count($embeddedBroadcast1->getGroups()));
        $this->assertEquals(1, count($embeddedBroadcast2->getGroups()));
        $this->assertTrue(in_array($group1, $embeddedBroadcast1->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $embeddedBroadcast1->getGroups()->toArray()));
        $this->assertFalse(in_array($group1, $embeddedBroadcast2->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $embeddedBroadcast2->getGroups()->toArray()));

        $mmsGroup1 = $this->repo->findWithGroupInEmbeddedBroadcast($group1);
        $mmsGroup2 = $this->repo->findWithGroupInEmbeddedBroadcast($group2);

        $this->assertEquals(1, count($mmsGroup1));
        $this->assertEquals(2, count($mmsGroup2));
        $this->assertTrue(in_array($mm1, $mmsGroup1->toArray()));
        $this->assertFalse(in_array($mm2, $mmsGroup1->toArray()));
        $this->assertTrue(in_array($mm1, $mmsGroup2->toArray()));
        $this->assertTrue(in_array($mm2, $mmsGroup2->toArray()));
    }

    public function testCountWithGroup()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

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

        $this->assertEquals(1, $this->repo->countWithGroup($group1));
        $this->assertEquals(2, $this->repo->countWithGroup($group2));
    }

    public function testCountWithGroupInEmbeddedBroadcast()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

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

        $this->assertEquals(1, $this->repo->countWithGroupInEmbeddedBroadcast($group1));
        $this->assertEquals(2, $this->repo->countWithGroupInEmbeddedBroadcast($group2));
    }

    public function testEmbeddedBroadcast()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $mm = new MultimediaObject();
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

        $this->assertEquals($type, $embBroadcast->getType());
        $this->assertEquals($name, $embBroadcast->getName());
        $this->assertEquals($password, $embBroadcast->getPassword());
        $this->assertTrue($embBroadcast->containsGroup($group1));
        $this->assertTrue($embBroadcast->containsGroup($group2));
        $this->assertEquals($name, $embBroadcast->__toString());
        $this->assertTrue($embBroadcast->isPasswordValid());

        $type2 = EmbeddedBroadcast::TYPE_GROUPS;
        $name2 = EmbeddedBroadcast::NAME_GROUPS;
        $embBroadcast->setType($type2);
        $embBroadcast->setName($name2);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $multimediaObject = $this->repo->find($mm->getId());
        $embBroadcast = $multimediaObject->getEmbeddedBroadcast();

        $this->assertEquals($type2, $embBroadcast->getType());
        $this->assertEquals($name2, $embBroadcast->getName());
        $this->assertEquals($name2.': '.$group1->getName().', '.$group2->getName(), $embBroadcast->__toString());

        $embBroadcast->removeGroup($group2);
        $this->dm->persist($mm);
        $this->dm->flush();

        $mmObj = $this->repo->find($multimediaObject->getId());
        $embBroad = $mmObj->getEmbeddedBroadcast();

        $this->assertTrue($embBroad->containsGroup($group1));
        $this->assertFalse($embBroad->containsGroup($group2));
    }

    public function testFindByEmbeddedBroadcast()
    {
        $mm1 = new MultimediaObject();
        $mm1->setTitle('test2');
        $this->dm->persist($mm1);
        $this->dm->flush();

        $mm2 = new MultimediaObject();
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

        $this->assertEquals(1, count($this->repo->findByEmbeddedBroadcast($mm1->getEmbeddedBroadcast())));
        $this->assertEquals(1, count($this->repo->findByEmbeddedBroadcast($mm2->getEmbeddedBroadcast())));
        $this->assertEquals(1, count($this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_PASSWORD)));
        $this->assertEquals(1, count($this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_PUBLIC)));
        $this->assertEquals(0, count($this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_LOGIN)));
        $this->assertEquals(0, count($this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_GROUPS)));

        $series1 = new Series();
        $series1->setTitle('series1');
        $series2 = new Series();
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
        $this->assertEquals(1, count($passwordSeriesField));
        $this->assertEquals(1, count($publicSeriesField));
        $this->assertEquals(0, count($loginSeriesField));
        $this->assertEquals(0, count($groupsSeriesField));

        $this->assertTrue(in_array($series1->getId(), $passwordSeriesField->toArray()));
        $this->assertFalse(in_array($series1->getId(), $publicSeriesField->toArray()));
        $this->assertFalse(in_array($series1->getId(), $loginSeriesField->toArray()));
        $this->assertFalse(in_array($series1->getId(), $groupsSeriesField->toArray()));

        $this->assertFalse(in_array($series2->getId(), $passwordSeriesField->toArray()));
        $this->assertTrue(in_array($series2->getId(), $publicSeriesField->toArray()));
        $this->assertFalse(in_array($series2->getId(), $loginSeriesField->toArray()));
        $this->assertFalse(in_array($series2->getId(), $groupsSeriesField->toArray()));

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
        $this->assertEquals(1, count($seriesGroups1));
        $this->assertEquals(0, count($seriesGroups2));
        $this->assertEquals(0, count($seriesGroups12));

        $this->assertTrue(in_array($series1->getId(), $seriesGroups1->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesGroups1->toArray()));
        $this->assertFalse(in_array($series1->getId(), $seriesGroups2->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesGroups2->toArray()));
        $this->assertFalse(in_array($series1->getId(), $seriesGroups12->toArray()));
        $this->assertFalse(in_array($series2->getId(), $seriesGroups12->toArray()));
    }

    public function testCountInSeriesWithPrototype()
    {
        $series1 = new Series();
        $series2 = new Series();

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $mm11 = new MultimediaObject();
        $mm12 = new MultimediaObject();
        $mm13 = new MultimediaObject();

        $mm21 = new MultimediaObject();
        $mm22 = new MultimediaObject();
        $mm23 = new MultimediaObject();
        $mm24 = new MultimediaObject();

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

        $this->assertEquals(3, $this->repo->countInSeriesWithPrototype($series1));
        $this->assertEquals(4, $this->repo->countInSeriesWithPrototype($series2));
    }

    public function testCountInSeriesWithEmbeddedBroadcast()
    {
        $series1 = new Series();
        $series2 = new Series();

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
        $mm12 = new MultimediaObject();
        $mm13 = new MultimediaObject();
        $mm14 = new MultimediaObject();

        $mm21 = new MultimediaObject();
        $mm22 = new MultimediaObject();
        $mm23 = new MultimediaObject();
        $mm24 = new MultimediaObject();

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

        $groups1 = [new \MongoId($group1->getId()), new \MongoId($group2->getId())];
        $groups2 = [new \MongoId($group2->getId()), new \MongoId($group1->getId())];
        $groups3 = [new \MongoId($group2->getId())];
        $groups4 = [new \MongoId($group1->getId())];
        $groups5 = [];

        $this->assertEquals(1, $this->repo->countInSeriesWithEmbeddedBroadcastType($series1, $typePublic));
        $this->assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastType($series2, $typePublic));
        $this->assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastType($series1, $typeLogin));
        $this->assertEquals(1, $this->repo->countInSeriesWithEmbeddedBroadcastType($series2, $typeLogin));

        $this->assertEquals(2, $this->repo->countInSeriesWithEmbeddedBroadcastPassword($series1, $typePassword, $password1));
        $this->assertEquals(1, $this->repo->countInSeriesWithEmbeddedBroadcastPassword($series1, $typePassword, $password2));
        $this->assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastPassword($series2, $typePassword, $password1));
        $this->assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastPassword($series2, $typePassword, $password2));

        $this->assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series1, $typeGroups, $groups1));
        $this->assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series1, $typeGroups, $groups2));
        $this->assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series1, $typeGroups, $groups3));
        $this->assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series1, $typeGroups, $groups4));
        $this->assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series1, $typeGroups, $groups5));

        $this->assertEquals(2, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series2, $typeGroups, $groups1));
        $this->assertEquals(2, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series2, $typeGroups, $groups2));
        $this->assertEquals(1, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series2, $typeGroups, $groups3));
        $this->assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series2, $typeGroups, $groups4));
        $this->assertEquals(0, $this->repo->countInSeriesWithEmbeddedBroadcastGroups($series2, $typeGroups, $groups5));
    }

    private function createPerson($name)
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

    private function createRole($name)
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

    private function createSeriesType($name)
    {
        $description = 'description';
        $series_type = new SeriesType();

        $series_type->setName($name);
        $series_type->setDescription($description);

        $this->dm->persist($series_type);
        $this->dm->flush();

        return $series_type;
    }

    private function createBroadcast($broadcastTypeId)
    {
        $broadcast = new Broadcast();
        $broadcast->setName(ucfirst($broadcastTypeId));
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd('password');
        if (0 === strcmp(Broadcast::BROADCAST_TYPE_PRI, $broadcastTypeId)) {
            $broadcast->setDefaultSel(true);
        } else {
            $broadcast->setDefaultSel(false);
        }
        $broadcast->setDescription(ucfirst($broadcastTypeId).' broadcast');

        $this->dm->persist($broadcast);
        $this->dm->flush();

        return $broadcast;
    }

    private function createGroup($key = 'Group1', $name = 'Group 1')
    {
        $group = new Group();

        $group->setKey($key);
        $group->setName($name);

        $this->dm->persist($group);
        $this->dm->flush();

        return $group;
    }
}
