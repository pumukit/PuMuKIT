<?php

namespace Pumukit\LiveBundle\Tests\Repository;

use Pumukit\LiveBundle\Document\Live;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class LiveRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(Live::class);

        $this->dm->getDocumentCollection(Live::class)->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testRepository()
    {
        $url = 'http://www.pumukit.com/liveo1';
        $passwd = 'password';
        $live_type = Live::LIVE_TYPE_FMS;
        $width = 640;
        $height = 480;
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
        $liveo->setWidth($width);
        $liveo->setHeight($height);
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

        $this->assertEquals(1, count($this->repo->findAll()));
    }
}
