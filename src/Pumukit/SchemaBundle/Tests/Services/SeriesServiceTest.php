<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\SeriesService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class SeriesServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $seriesService;
    private $seriesDispatcher;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository(Series::class)
        ;
        $this->seriesService = static::$kernel->getContainer()
            ->get('pumukitschema.series')
        ;
        $this->seriesDispatcher = static::$kernel->getContainer()
            ->get('pumukitschema.series_dispatcher')
        ;

        $this->dm->getDocumentCollection(Series::class)->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->seriesService = null;
        $this->seriesDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testResetMagicUrl()
    {
        $series = new Series();

        $this->dm->persist($series);
        $this->dm->flush();

        $secret = $series->getSecret();

        $series = $this->repo->find($series->getId());
        $this->assertEquals($secret, $series->getSecret());

        $seriesService = new SeriesService($this->dm, $this->seriesDispatcher);

        $newSecret = $seriesService->resetMagicUrl($series);

        $this->assertNotEquals($secret, $series->getSecret());
        $this->assertEquals($newSecret, $series->getSecret());
    }

    public function testSameEmbeddedBroadcast()
    {
        $series1 = new Series();

        $this->dm->persist($series1);
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

        $mm11 = new MultimediaObject();
        $mm12 = new MultimediaObject();
        $mm13 = new MultimediaObject();
        $mm14 = new MultimediaObject();

        $mm11->setSeries($series1);
        $mm12->setSeries($series1);
        $mm13->setSeries($series1);
        $mm14->setSeries($series1);

        $mm11->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm12->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        $mm13->setStatus(MultimediaObject::STATUS_BLOCKED);

        $mm11->setEmbeddedBroadcast($embeddedBroadcast11);
        $mm12->setEmbeddedBroadcast($embeddedBroadcast12);
        $mm13->setEmbeddedBroadcast($embeddedBroadcast13);
        $mm14->setEmbeddedBroadcast($embeddedBroadcast14);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm14);
        $this->dm->flush();

        $this->assertFalse($this->seriesService->sameEmbeddedBroadcast($series1));

        $embeddedBroadcast11 = $mm11->getEmbeddedBroadcast();
        $embeddedBroadcast12 = $mm12->getEmbeddedBroadcast();
        $embeddedBroadcast13 = $mm13->getEmbeddedBroadcast();
        $embeddedBroadcast14 = $mm14->getEmbeddedBroadcast();
        $embeddedBroadcast11->setType($typePublic);
        $embeddedBroadcast12->setType($typePublic);
        $embeddedBroadcast13->setType($typePublic);
        $embeddedBroadcast14->setType($typePublic);
        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm14);
        $this->dm->flush();

        $this->assertTrue($this->seriesService->sameEmbeddedBroadcast($series1));

        $embeddedBroadcast11->setType($typePassword);
        $embeddedBroadcast12->setType($typePassword);
        $embeddedBroadcast13->setType($typePassword);
        $embeddedBroadcast14->setType($typePassword);
        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm14);
        $this->dm->flush();

        $this->assertFalse($this->seriesService->sameEmbeddedBroadcast($series1));

        $embeddedBroadcast11->setPassword($password2);
        $embeddedBroadcast12->setPassword($password2);
        $embeddedBroadcast13->setPassword($password2);
        $embeddedBroadcast14->setPassword($password2);
        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm14);
        $this->dm->flush();

        $this->assertTrue($this->seriesService->sameEmbeddedBroadcast($series1));

        $embeddedBroadcast11->setType($typeGroups);
        $embeddedBroadcast12->setType($typeGroups);
        $embeddedBroadcast13->setType($typeGroups);
        $embeddedBroadcast14->setType($typeGroups);
        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm14);
        $this->dm->flush();

        $this->assertFalse($this->seriesService->sameEmbeddedBroadcast($series1));

        $embeddedBroadcast11 = $this->removeGroups($embeddedBroadcast11);
        $embeddedBroadcast11->addGroup($group1);
        $embeddedBroadcast11->addGroup($group2);
        $embeddedBroadcast12 = $this->removeGroups($embeddedBroadcast12);
        $embeddedBroadcast12->addGroup($group1);
        $embeddedBroadcast12->addGroup($group2);
        $embeddedBroadcast13 = $this->removeGroups($embeddedBroadcast13);
        $embeddedBroadcast13->addGroup($group1);
        $embeddedBroadcast13->addGroup($group2);
        $embeddedBroadcast14 = $this->removeGroups($embeddedBroadcast14);
        $embeddedBroadcast14->addGroup($group1);
        $embeddedBroadcast14->addGroup($group2);
        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm14);
        $this->dm->flush();

        $this->assertTrue($this->seriesService->sameEmbeddedBroadcast($series1));
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

    private function removeGroups(EmbeddedBroadcast $embeddedBroadcast)
    {
        foreach ($embeddedBroadcast->getGroups() as $group) {
            $embeddedBroadcast->removeGroup($group);
        }

        return $embeddedBroadcast;
    }
}
