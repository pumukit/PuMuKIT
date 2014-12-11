<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;

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

    public function testSettersAndGetters()
    {
	$locale = 'en';
        $mmobj1 = new MultimediaObject();
	$mmobj1->setLocale($locale);
	$mmobj1->setTitle('Multimedia Object 1', $locale);
	$broadcastTypeId = Broadcast::BROADCAST_TYPE_PRI;
	$name = ucfirst($broadcastTypeId);
	$passwd = 'password';
	$defaultSel = true;
	$description = ucfirst($broadcastTypeId).' broadcast';
	
	$this->dm->persist($mmobj1);

	$broadcast = new Broadcast();
	$broadcast->setLocale($locale);
	$broadcast->addMultimediaObject($mmobj1);
	$broadcast->setName($name);
	$broadcast->setBroadcastTypeId($broadcastTypeId);
	$broadcast->setPasswd($passwd);
	$broadcast->setDefaultSel($defaultSel);
	$broadcast->setDescription($description);

	$this->dm->persist($broadcast);
	$this->dm->flush();

	$this->assertEquals(array($mmobj1), $broadcast->getMultimediaObjects()->toArray());
	$this->assertEquals($locale, $broadcast->getLocale());
	$this->assertEquals($name, $broadcast->getName());
	$this->assertEquals($broadcastTypeId, $broadcast->getBroadcastTypeId());
	$this->assertEquals($passwd, $broadcast->getPasswd());
	$this->assertEquals($defaultSel, $broadcast->getDefaultSel());
	$this->assertEquals($description, $broadcast->getDescription());

	$mmobj2 = new MultimediaObject();
	$mmobj2->setLocale($locale);
	$mmobj2->setTitle('Multimedia Object 2', $locale);
	$mmobj3 = new MultimediaObject();
	$mmobj3->setLocale($locale);
	$mmobj3->setTitle('Multimedia Object 3', $locale);

	$this->dm->persist($mmobj2);
	$this->dm->persist($mmobj3);
	
	$broadcast->addMultimediaObject($mmobj2);
	$broadcast->addMultimediaObject($mmobj3);

	$this->dm->persist($broadcast);
	$this->dm->flush();

	$mmobjs = array($mmobj1, $mmobj2, $mmobj3);
	
	$this->assertEquals($mmobjs, $broadcast->getMultimediaObjects()->toArray());

	$broadcast->removeMultimediaObject($mmobj1);
	$this->dm->persist($broadcast);
	$this->dm->flush();

	$mmobjs = array($mmobj2, $mmobj3);
	$this->assertEquals(count($mmobjs), count($broadcast->getMultimediaObjects()));

	$mmobjsRepo = $this->dm
	    ->getRepository('PumukitSchemaBundle:MultimediaObject');
	$this->assertEquals($mmobj1, $mmobjsRepo->find($mmobj1->getId()));

	$this->assertEquals(false, $broadcast->containsMultimediaObject($mmobj1));
	$this->assertEquals(true, $broadcast->containsMultimediaObject($mmobj2));
	$this->assertEquals(true, $broadcast->containsMultimediaObject($mmobj3));
    }

    private function createBroadcast($broadcastTypeId)
    {
        $mmobj = new MultimediaObject();
	$locale = 'en';
	$mmobj->setLocale($locale);
	$mmobj->setTitle('Multimedia Object', $locale);
	$name = ucfirst($broadcastTypeId);
	$passwd = 'password';
	$defaultSel = $broadcastTypeId == Broadcast::BROADCAST_TYPE_PRI;
	$description = ucfirst($broadcastTypeId).' broadcast';
	
	$this->dm->persist($mmobj);

	$broadcast = new Broadcast();
	$broadcast->setLocale($locale);
	$broadcast->addMultimediaObject($mmobj);
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