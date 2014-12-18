<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Broadcast;

class BroadcastTest extends \PHPUnit_Framework_TestCase
{
    public function testSetterAndGetter()
    {
        $locale = 'en';
        $broadcastTypeId = Broadcast::BROADCAST_TYPE_PRI;
        $nameEn = 'Private';
        $passwd = 'password';
        $defaultSel = true;
        $descriptionEn = 'Private broadcast';

        $broadcast = new Broadcast();
        $broadcast->setLocale($locale);
        $broadcast->setName($nameEn, $locale);
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd($passwd);
        $broadcast->setDefaultSel($defaultSel);
        $broadcast->setDescription($descriptionEn, $locale);

        $this->assertEquals($locale, $broadcast->getLocale());
        $this->assertEquals($nameEn, $broadcast->getName());
        $this->assertEquals($nameEn, $broadcast->getName($locale));
        $this->assertEquals($broadcastTypeId, $broadcast->getBroadcastTypeId());
        $this->assertEquals($passwd, $broadcast->getPasswd());
        $this->assertEquals($defaultSel, $broadcast->getDefaultSel());
        $this->assertEquals($descriptionEn, $broadcast->getDescription());
        $this->assertEquals($descriptionEn, $broadcast->getDescription($locale));

        $nameEs = 'Privado';
        $i18nName = array('en' => $nameEn, 'es' => $nameEs);
        $descriptionEs = 'Difusión privada';
        $i18nDescription = array('en' => $descriptionEn, 'es' => $descriptionEs);

        $broadcast->setI18nName($i18nName);
        $broadcast->setI18nDescription($i18nDescription);

        $this->assertEquals($i18nName, $broadcast->getI18nName());
        $this->assertEquals($i18nDescription, $broadcast->getI18nDescription());

        $this->assertNull($broadcast->getName('fr'));
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

        $this->assertEquals($broadcast->getBroadcastTypeId(), $broadcast->__toString());
    }

    private function createBroadcast($broadcastTypeId)
    {
        $locale = 'en';
        $nameEn = ucfirst($broadcastTypeId);
        $passwd = 'password';
        $defaultSel = true;
        $descriptionEn = ucfirst($broadcastTypeId).' broadcast';

        $broadcast = new Broadcast();
        $broadcast->setLocale($locale);
        $broadcast->setName($nameEn, $locale);
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd($passwd);
        $broadcast->setDefaultSel($defaultSel);
        $broadcast->setDescription($descriptionEn, $locale);

        return $broadcast;
    }
}
