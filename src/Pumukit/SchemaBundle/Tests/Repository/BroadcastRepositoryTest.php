<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @deprecated in version 2.3
 *
 * @internal
 * @coversNothing
 */
class BroadcastRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository(Broadcast::class)
        ;

        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Broadcast::class)
            ->remove([])
        ;
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $broadcastPrivate = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI, 'private');
        $this->assertEquals(1, count($this->repo->findAll()));

        $broadcastPublic = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB, 'public');
        $this->assertEquals(2, count($this->repo->findAll()));
    }

    public function testFindDistinctIdsByBroadcastTypeId()
    {
        $private1 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI, 'private1');
        $public1 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB, 'public1');
        $public2 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB, 'public2');
        $private2 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI, 'private2');
        $corporative1 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_COR, 'corporative1');

        $privates = $this->repo->findDistinctIdsByBroadcastTypeId(Broadcast::BROADCAST_TYPE_PRI)->toArray();

        $this->assertTrue(in_array($private1->getId(), $privates));
        $this->assertTrue(in_array($private2->getId(), $privates));
        $this->assertFalse(in_array($public1->getId(), $privates));
        $this->assertFalse(in_array($public2->getId(), $privates));
        $this->assertFalse(in_array($corporative1->getId(), $privates));

        $publics = $this->repo->findDistinctIdsByBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB)->toArray();

        $this->assertFalse(in_array($private1->getId(), $publics));
        $this->assertFalse(in_array($private2->getId(), $publics));
        $this->assertTrue(in_array($public1->getId(), $publics));
        $this->assertTrue(in_array($public2->getId(), $publics));
        $this->assertFalse(in_array($corporative1->getId(), $publics));

        $corporatives = $this->repo->findDistinctIdsByBroadcastTypeId(Broadcast::BROADCAST_TYPE_COR)->toArray();

        $this->assertFalse(in_array($private1->getId(), $corporatives));
        $this->assertFalse(in_array($private2->getId(), $corporatives));
        $this->assertFalse(in_array($public1->getId(), $corporatives));
        $this->assertFalse(in_array($public2->getId(), $corporatives));
        $this->assertTrue(in_array($corporative1->getId(), $corporatives));
    }

    private function createBroadcast($broadcastTypeId, $name)
    {
        $locale = 'en';
        $passwd = 'password';
        $defaultSel = Broadcast::BROADCAST_TYPE_PRI == $broadcastTypeId;
        $description = ucfirst($broadcastTypeId).' broadcast';

        $broadcast = new Broadcast();
        $broadcast->setLocale($locale);
        $broadcast->setName($name);
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd($passwd);
        $broadcast->setDefaultSel($defaultSel);
        $broadcast->setDescription($description);

        $this->dm->persist($broadcast);
        $this->dm->flush();

        return $broadcast;
    }
}
