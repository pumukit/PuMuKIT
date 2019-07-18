<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Pumukit\SchemaBundle\Document\Live;

/**
 * @internal
 * @coversNothing
 */
class EmbeddedEventTest extends TestCase
{
    public function testSetterAndGetter()
    {
        $name = 'Embedded Event 1';
        $description = 'Description of the event';
        $author = 'Author of the event';
        $producer = 'Producer of the event';
        $place = 'Place of the event';
        $date = new \DateTime('2018-02-01 09:00:00');
        $duration = 90;
        $display = true;
        $create_serial = false;
        $embeddedEventSession = new ArrayCollection();
        $live = new Live();
        $url = 'https://test.com';
        $alreadyHeldMessage = ['en' => 'The event has been already held.'];
        $notYetHeldMessage = ['en' => 'The event has not yet been alread held.'];
        $locale = 'en';

        $embeddedEvent = new EmbeddedEvent();
        $embeddedEvent->setName($name);
        $embeddedEvent->setDescription($description);
        $embeddedEvent->setAuthor($author);
        $embeddedEvent->setProducer($producer);
        $embeddedEvent->setPlace($place);
        $embeddedEvent->setDate($date);
        $embeddedEvent->setDuration($duration);
        $embeddedEvent->setDisplay($display);
        $embeddedEvent->setCreateSerial($create_serial);
        $embeddedEvent->setEmbeddedEventSession($embeddedEventSession);
        $embeddedEvent->setLive($live);
        $embeddedEvent->setUrl($url);
        $embeddedEvent->setAlreadyHeldMessage($alreadyHeldMessage);
        $embeddedEvent->setNotYetHeldMessage($notYetHeldMessage);
        $embeddedEvent->setLocale($locale);

        $this->assertEquals($name, $embeddedEvent->getName());
        $this->assertEquals($description, $embeddedEvent->getDescription());
        $this->assertEquals($author, $embeddedEvent->getAuthor());
        $this->assertEquals($producer, $embeddedEvent->getProducer());
        $this->assertEquals($place, $embeddedEvent->getPlace());
        $this->assertEquals($date, $embeddedEvent->getDate());
        $this->assertEquals($duration, $embeddedEvent->getDuration());
        $this->assertEquals($display, $embeddedEvent->isDisplay());
        $this->assertEquals($create_serial, $embeddedEvent->isCreateSerial());
        $this->assertEquals($embeddedEventSession->toArray(), $embeddedEvent->getEmbeddedEventSession());
        $this->assertEquals($live, $embeddedEvent->getLive());
        $this->assertEquals($url, $embeddedEvent->getUrl());
        $this->assertEquals($alreadyHeldMessage, $embeddedEvent->getAlreadyHeldMessage());
        $this->assertEquals($notYetHeldMessage, $embeddedEvent->getNotYetHeldMessage());
        $this->assertEquals($locale, $embeddedEvent->getLocale());
    }
}
