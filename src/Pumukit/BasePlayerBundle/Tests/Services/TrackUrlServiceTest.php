<?php

namespace Pumukit\WebTVBundle\Tests\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TrackUrlServiceTest extends WebTestCase
{
    private $dm;
    private $trackurlService;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);
        $container = static::$kernel->getContainer();
        $this->dm = $container->get('doctrine_mongodb')->getManager();
        $this->trackurlService = $container->get('pumukit_baseplayer.trackurl');
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->trackurlService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGenerateTrackFileUrl()
    {
        $track = new Track();
        $track->setUrl('funnyurl.mp4');
        $mmobj = new MultimediaObject();
        $mmobj->addTrack($track);
        $this->dm->persist($mmobj);
        $this->dm->flush();

        $this->assertEquals($mmobj->getNumview(), 0);

        $client = static::createClient();
        $genUrl = $this->trackurlService->generateTrackFileUrl($track);
        // @Route("/trackfile/{id}.{ext}", name="pumukit_trackfile_index" )
        $crawler = $client->request('GET', $genUrl);
        $this->assertEquals( $genUrl, 'trackfile/'.$track->getId().'.mp4');
        $this->assertEquals( 302, $client->getResponse()->getStatusCode());
        $this->assertEquals( $track->getUrl(), $client->getResponse()->getTargetUrl());
        $this->assertEquals($mmobj->getNumview(), 1);
    }
}
