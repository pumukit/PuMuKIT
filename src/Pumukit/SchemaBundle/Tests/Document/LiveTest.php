<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Live;

/**
 * @internal
 * @coversNothing
 */
class LiveTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $url = 'http://www.pumukit.com/liveo1';
        $passwd = 'password';
        $live_type = Live::LIVE_TYPE_FMS;
        $qualities = 'high';
        $ip_source = '127.0.0.1';
        $source_name = 'localhost';
        $index_play = true;
        $broadcasting = true;
        $debug = true;
        $locale = 'en';
        $name = 'liveo 1';
        $description = 'liveo channel';

        $liveo = new Live();

        $liveo->setUrl($url);
        $liveo->setPasswd($passwd);
        $liveo->setLiveType($live_type);
        $liveo->setQualities($qualities);
        $liveo->setIpSource($ip_source);
        $liveo->setSourceName($source_name);
        $liveo->setIndexPlay($index_play);
        $liveo->setBroadcasting($broadcasting);
        $liveo->setDebug($debug);
        $liveo->setLocale($locale);
        $liveo->setName($name, $locale);
        $liveo->setDescription($description, $locale);

        static::assertEquals($url, $liveo->getUrl());
        static::assertEquals($passwd, $liveo->getPasswd());
        static::assertEquals($live_type, $liveo->getLiveType());
        static::assertEquals($qualities, $liveo->getQualities());
        static::assertEquals($ip_source, $liveo->getIpSource());
        static::assertEquals($source_name, $liveo->getSourceName());
        static::assertEquals($index_play, $liveo->getIndexPlay());
        static::assertEquals($broadcasting, $liveo->getBroadcasting());
        static::assertEquals($debug, $liveo->getDebug());
        static::assertEquals($locale, $liveo->getLocale());
        static::assertEquals($name, $liveo->getName($liveo->getLocale()));
        static::assertEquals($name, $liveo->getName());
        static::assertEquals($description, $liveo->getDescription($liveo->getLocale()));
        static::assertEquals($description, $liveo->getDescription());

        $liveo->setDescription($description);
        static::assertEquals($description, $liveo->getDescription($liveo->getLocale()));

        $nameEs = 'directo 1';
        $i18nName = ['en' => $name, 'es' => $nameEs];
        $liveo->setI18nName($i18nName);
        static::assertEquals($i18nName, $liveo->getI18nName());

        $descriptionEs = 'canal de directos';
        $i18nDescription = ['en' => $description, 'es' => $descriptionEs];
        $liveo->setI18nDescription($i18nDescription);
        static::assertEquals($i18nDescription, $liveo->getI18nDescription());

        $name = null;
        $liveo->setName($name, $locale);
        static::assertEquals(null, $liveo->getName($liveo->getLocale()));

        $description = null;
        $liveo->setDescription($description, $locale);
        static::assertEquals(null, $liveo->getDescription($liveo->getLocale()));
    }

    public function testCloneResource()
    {
        $live = new Live();

        static::assertEquals($live, $live->cloneResource());
    }

    public function testToString()
    {
        $live = new Live();

        static::assertEquals($live->getName(), $live->__toString());
    }

    public function testIsValidLiveType()
    {
        $live = new Live();

        $live_type = Live::LIVE_TYPE_FMS;
        $live->setLiveType($live_type);

        static::assertTrue($live->isValidLiveType());
    }
}
