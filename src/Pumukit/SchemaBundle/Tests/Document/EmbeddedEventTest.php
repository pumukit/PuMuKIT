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
        $alreadyHeldMessage = 'The event has been already held.';
        $notYetHeldMessage = 'The event has not yet been already held.';
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
        $embeddedEvent->setAlreadyHeldMessage($alreadyHeldMessage, 'en');
        $embeddedEvent->setNotYetHeldMessage($notYetHeldMessage, 'en');
        $embeddedEvent->setLocale($locale);

        static::assertEquals($name, $embeddedEvent->getName());
        static::assertEquals($description, $embeddedEvent->getDescription());
        static::assertEquals($author, $embeddedEvent->getAuthor());
        static::assertEquals($producer, $embeddedEvent->getProducer());
        static::assertEquals($place, $embeddedEvent->getPlace());
        static::assertEquals($date, $embeddedEvent->getDate());
        static::assertEquals($duration, $embeddedEvent->getDuration());
        static::assertEquals($display, $embeddedEvent->isDisplay());
        static::assertEquals($create_serial, $embeddedEvent->isCreateSerial());
        static::assertEquals($embeddedEventSession->toArray(), $embeddedEvent->getEmbeddedEventSession());
        static::assertEquals($live, $embeddedEvent->getLive());
        static::assertEquals($url, $embeddedEvent->getUrl());
        static::assertEquals($alreadyHeldMessage, $embeddedEvent->getAlreadyHeldMessage());
        static::assertEquals($notYetHeldMessage, $embeddedEvent->getNotYetHeldMessage());
        static::assertEquals($locale, $embeddedEvent->getLocale());
    }
}
