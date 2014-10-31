<?php

namespace Pumukit\DirectBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Pumukit\DirectBundle\Document\Event;

class EventRepositoryTest extends WebTestCase
{
  private $dm;
  private $repo;

  public function setUp()
  {
    $options = array('environment'=>'test');
    $kernel = static::createKernel($options);
    $kernel->boot();
    
    $this->dm = $kernel->getContainer()->get('doctrine_mongodb')->getManager();
    $this->repo = $this->dm->getRepository('PumukitDirectBundle:Event');

    $this->dm->getDocumentCollection('PumukitDirectBundle:Event')->remove(array());
    $this->dm->flush();
  }

  public function testRepository()
  {
    $directo = new Direct();
    $direct_id = $directo->getId();
    $name = 'event name';
    $place = 'event place';
    $date = new Timestamp();
    $duration = '60';
    $display = 0;
    $create_serial = 0;
    
    $event = new Event();
    
    $event->setDirectId($direct_id);
    $event->setName($name);
    $event->setPlace($place);
    $event->setDate($date);
    $event->setDuration($duration);
    $event->setDisplay($display);
    $event->setCreateSerial($create_serial);

    $this->dm->persist($directo);
    $this->dm->flush();

    $this->assertEquals(1, count($this->repo->findAll()));
  }
}