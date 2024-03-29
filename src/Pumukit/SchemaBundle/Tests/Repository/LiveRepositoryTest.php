<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Live;

/**
 * @internal
 *
 * @coversNothing
 */
class LiveRepositoryTest extends PumukitTestCase
{
    private $repo;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(Live::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        gc_collect_cycles();
    }

    public function testRepository(): void
    {
        $url = 'http://www.pumukit.com/liveo1';
        $passwd = 'password';
        $live_type = Live::LIVE_TYPE_WOWZA;
        $qualities = 'high';
        $ip_source = '127.0.0.1';
        $source_name = 'localhost';
        $index_play = 1;
        $broadcasting = 1;
        $debug = 1;
        $locale = 'es';
        $name = 'liveo 1';
        $description = 'canal de liveo';

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

        $this->dm->persist($liveo);
        $this->dm->flush();

        static::assertCount(1, $this->repo->findAll());
    }
}
