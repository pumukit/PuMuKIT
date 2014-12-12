<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class BroadcastTest extends \PHPUnit_Framework_TestCase
{
    public function testSetterAndGetter()
    {
	$locale = 'en';
        $mmobj1 = new MultimediaObject();
	$mmobj1->setLocale($locale);
	$mmobj1->setTitle('Multimedia Object 1', $locale);
	$broadcastTypeId = Broadcast::BROADCAST_TYPE_PRI;
	$nameEn = 'Private';
	$passwd = 'password';
	$defaultSel = true;
	$descriptionEn = 'Private broadcast';
	
	$broadcast = new Broadcast();
	$broadcast->setLocale($locale);
	$broadcast->addMultimediaObject($mmobj1);
	$broadcast->setName($nameEn, $locale);
	$broadcast->setBroadcastTypeId($broadcastTypeId);
	$broadcast->setPasswd($passwd);
	$broadcast->setDefaultSel($defaultSel);
	$broadcast->setDescription($descriptionEn, $locale);

	$this->assertEquals(array($mmobj1), $broadcast->getMultimediaObjects());
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

	$mmobj2 = new MultimediaObject();
	$mmobj2->setLocale($locale);
	$mmobj2->setTitle('Multimedia Object 2', $locale);
	$mmobj3 = new MultimediaObject();
	$mmobj3->setLocale($locale);
	$mmobj3->setTitle('Multimedia Object 3', $locale);

	$broadcast->addMultimediaObject($mmobj2);
	$broadcast->addMultimediaObject($mmobj3);

	$mmobjs = array($mmobj1, $mmobj2, $mmobj3);
	
	$this->assertEquals($mmobjs, $broadcast->getMultimediaObjects());
	/*
	$broadcast->removeMultimediaObject($mmobj1);

	$mmobjs = array($mmobj2, $mmobj3);
	$this->assertEquals($mmobjs, $broadcast->getMultimediaObjects());

	$this->assertEquals(false, $broadcast->containsMultimediaObject($mmobj1));
	$this->assertEquals(true, $broadcast->containsMultimediaObject($mmobj2));
	$this->assertEquals(true, $broadcast->containsMultimediaObject($mmobj3));
	*/
    }



}