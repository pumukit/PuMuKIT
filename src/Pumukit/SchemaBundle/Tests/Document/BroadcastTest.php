<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Broadcast;
// @deprecated in version 2.3
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @internal
 * @coversNothing
 */
class BroadcastTest extends TestCase
{
    public function testSetterAndGetter()
    {
        $locale = 'en';
        $broadcastTypeId = Broadcast::BROADCAST_TYPE_PRI;
        $name = 'Private';
        $passwd = 'password';
        $defaultSel = true;
        $descriptionEn = 'Private broadcast';

        $broadcast = new Broadcast();
        $broadcast->setLocale($locale);
        $broadcast->setName($name);
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd($passwd);
        $broadcast->setDefaultSel($defaultSel);
        $broadcast->setDescription($descriptionEn, $locale);

        $this->assertEquals($locale, $broadcast->getLocale());
        $this->assertEquals($name, $broadcast->getName());
        $this->assertEquals($broadcastTypeId, $broadcast->getBroadcastTypeId());
        $this->assertEquals($passwd, $broadcast->getPasswd());
        $this->assertEquals($defaultSel, $broadcast->getDefaultSel());
        $this->assertEquals($descriptionEn, $broadcast->getDescription());
        $this->assertEquals($descriptionEn, $broadcast->getDescription($locale));

        $descriptionEs = 'Difusión privada';
        $i18nDescription = ['en' => $descriptionEn, 'es' => $descriptionEs];

        $broadcast->setI18nDescription($i18nDescription);

        $this->assertEquals($i18nDescription, $broadcast->getI18nDescription());

        $this->assertEquals('', $broadcast->getDescription('fr'));
        $this->assertNull($broadcast->getId());
    }

    public function testCloneResource()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);

        $this->assertEquals($broadcast, $broadcast->cloneResource());
    }

    public function testToString()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);

        $this->assertEquals($broadcast->getName(), $broadcast->__toString());
    }

    public function testNumberMultimediaObjects()
    {
        $privateBroadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);
        $publicBroadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);
        $corporativeBroadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_COR);

        $mm1 = new MultimediaObject();
        $mm2 = new MultimediaObject();
        $mm3 = new MultimediaObject();
        $mm4 = new MultimediaObject();
        $mm5 = new MultimediaObject();

        $mm1->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        $mm2->setStatus(MultimediaObject::STATUS_BLOCKED);
        $mm3->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm4->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm5->setStatus(MultimediaObject::STATUS_HIDDEN);

        $mm1->setBroadcast($privateBroadcast);
        $mm2->setBroadcast($privateBroadcast);
        $mm3->setBroadcast($publicBroadcast);
        $mm4->setBroadcast($corporativeBroadcast);
        $mm5->setBroadcast($privateBroadcast);

        $this->assertEquals(2, $privateBroadcast->getNumberMultimediaObjects());
        $this->assertEquals(1, $publicBroadcast->getNumberMultimediaObjects());
        $this->assertEquals(1, $corporativeBroadcast->getNumberMultimediaObjects());

        $publicBroadcast->setNumberMultimediaObjects(3);
        $this->assertEquals(3, $publicBroadcast->getNumberMultimediaObjects());

        $privateBroadcast->setNumberMultimediaObjects(3);
        $privateBroadcast->decreaseNumberMultimediaObjects();
        $this->assertEquals(2, $privateBroadcast->getNumberMultimediaObjects());
    }

    private function createBroadcast($broadcastTypeId)
    {
        $locale = 'en';
        $name = ucfirst($broadcastTypeId);
        $passwd = 'password';
        $defaultSel = true;
        $descriptionEn = ucfirst($broadcastTypeId).' broadcast';

        $broadcast = new Broadcast();
        $broadcast->setLocale($locale);
        $broadcast->setName($name);
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd($passwd);
        $broadcast->setDefaultSel($defaultSel);
        $broadcast->setDescription($descriptionEn, $locale);

        return $broadcast;
    }
}
