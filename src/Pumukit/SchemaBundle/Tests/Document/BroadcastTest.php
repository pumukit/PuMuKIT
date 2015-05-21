<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Broadcast;

class BroadcastTest extends \PHPUnit_Framework_TestCase
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
        $i18nDescription = array('en' => $descriptionEn, 'es' => $descriptionEs);

        $broadcast->setI18nDescription($i18nDescription);

        $this->assertEquals($i18nDescription, $broadcast->getI18nDescription());

        $this->assertNull($broadcast->getDescription('fr'));
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
