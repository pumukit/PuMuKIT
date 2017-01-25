<?php

namespace Pumukit\BasePlayerBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TrackUrlServiceTest extends WebTestCase
{
    private $client;
    private $dm;
    private $trackurlService;

    public function setUp()
    {
        $options = array('environment' => 'test');

        $this->client = static::createClient();
        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->trackurlService = static::$kernel->getContainer()->get('pumukit_baseplayer.trackurl');
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->mmobjRepo = null;
        $this->trackurlService = null;
        $this->client = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGenerateTrackFileUrl()
    {
        $track = new Track();
        $series = new Series();
        $mmobj = new MultimediaObject();
        $track->setUrl('funnyurl.mp4');
        $mmobj->setSeries($series);
        $mmobj->addTrack($track);
        $this->dm->persist($series);
        $this->dm->persist($mmobj);
        $this->dm->flush();

        $this->assertEquals(0, $mmobj->getNumview());

        $genUrl = $this->trackurlService->generateTrackFileUrl($track);
        $this->client->request('GET', $genUrl);
        // @Route("/trackfile/{id}.{ext}", name="pumukit_trackfile_index" )
        $this->assertEquals($genUrl, '/trackfile/'.$track->getId().'.mp4');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertEquals($track->getUrl(), $this->client->getResponse()->getTargetUrl());
        //Reload mmobj to check for new views.
        $this->dm->clear();
        $mmobj = $this->mmobjRepo->find($mmobj->getId());
        $this->assertEquals(1, $mmobj->getNumview());
        $this->client->request('GET', $genUrl, array(), array(), array('HTTP_RANGE' => 'bytes=123-246'));
        $this->dm->clear();
        $mmobj = $this->mmobjRepo->find($mmobj->getId());
        $this->assertEquals(1, $mmobj->getNumview());
        //Views should work if range = 0
        $this->client->request('GET', $genUrl, array(), array(), array('HTTP_RANGE' => 'bytes=0-1256'));
        $this->client->request('GET', $genUrl, array(), array(), array('HTTP_RANGE' => 'bytes=0-'));
        $this->dm->clear();
        $mmobj = $this->mmobjRepo->find($mmobj->getId());
        $this->assertEquals(3, $mmobj->getNumview());
        //Start should also work
        $this->client->request('GET', $genUrl, array(), array(), array('HTTP_START' => 0));
        //xTreme case: If either 'start' or 'range' is valid, it adds a numView.
        $this->client->request('GET', $genUrl, array(), array(), array('HTTP_START' => 1254, 'HTTP_RANGE' => 'bytes=0-1256'));
        $this->client->request('GET', $genUrl, array(), array(), array('HTTP_START' => 0, 'HTTP_RANGE' => 'bytes=123-1256'));
        $this->client->request('GET', $genUrl, array(), array(), array('HTTP_START' => 1254, 'HTTP_RANGE' => 'bytes=123-1256'));
        $this->dm->clear();
        $mmobj = $this->mmobjRepo->find($mmobj->getId());
        $this->assertEquals(6, $mmobj->getNumview());
        //With GET params
        $getParams = '?1=2&forcedl=1';
        $genUrl = $this->trackurlService->generateTrackFileUrl($track);
        $this->client->request('GET', $genUrl.$getParams);
        // @Route("/trackfile/{id}.{ext}", name="pumukit_trackfile_index" )
        $this->assertEquals($genUrl, '/trackfile/'.$track->getId().'.mp4');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertEquals($track->getUrl().$getParams, $this->client->getResponse()->getTargetUrl());
    }
}
