<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Broadcast;

class BroadcastRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function setUp()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
        ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
        ->getRepository('PumukitSchemaBundle:Broadcast');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
        ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')
        ->remove(array());
        $this->dm->flush();
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $broadcastPrivate = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);
        $this->assertEquals(1, count($this->repo->findAll()));

        $broadcastPublic = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);
        $this->assertEquals(2, count($this->repo->findAll()));
    }

    private function createBroadcast($broadcastTypeId)
    {
        $locale = 'en';
        $name = ucfirst($broadcastTypeId);
        $passwd = 'password';
        $defaultSel = $broadcastTypeId == Broadcast::BROADCAST_TYPE_PRI;
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
