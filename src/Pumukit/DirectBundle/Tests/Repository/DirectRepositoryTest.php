<?php

namespace Pumukit\DirectBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\DirectBundle\Document\Direct;

class DirectRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function setUp()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository('PumukitDirectBundle:Direct');

        $this->dm->getDocumentCollection('PumukitDirectBundle:Direct')->remove(array());
        $this->dm->flush();
    }

    public function testRepository()
    {
        $url = 'http://www.pumukit2.com/directo1';
        $passwd = 'password';
        $direct_type = Direct::DIRECT_TYPE_FMS;
        $width = 640;
        $height = 480;
        $qualities = 'high';
        $ip_source = '127.0.0.1';
        $source_name = 'localhost';
        $index_play = 1;
        $broadcasting = 1;
        $debug = 1;
        $locale = 'es';
        $name = 'directo 1';
        $description = 'canal de directo';

        $directo = new Direct();

        $directo->setUrl($url);
        $directo->setPasswd($passwd);
        $directo->setDirectType($direct_type);
        $directo->setWidth($width);
        $directo->setHeight($height);
        $directo->setQualities($qualities);
        $directo->setIpSource($ip_source);
        $directo->setSourceName($source_name);
        $directo->setIndexPlay($index_play);
        $directo->setBroadcasting($broadcasting);
        $directo->setDebug($debug);
        $directo->setLocale($locale);
        $directo->setName($name, $locale);
        $directo->setDescription($description, $locale);

        $this->dm->persist($directo);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findAll()));
    }
}
