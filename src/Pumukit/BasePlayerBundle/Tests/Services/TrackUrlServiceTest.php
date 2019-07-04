<?php

namespace Pumukit\BasePlayerBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class TrackUrlServiceTest extends WebTestCase
{
    private $client;
    private $dm;
    private $trackurlService;
    private $mmobjRepo;

    public function setUp()
    {
        $options = ['environment' => 'test'];

        $this->client = static::createClient();
        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
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
        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $tag = new Tag();
        $tag->setCod('PUCHWEBTV');
        $mmobj->addTag($tag);
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
        $this->client->request('GET', $genUrl, [], [], ['HTTP_RANGE' => 'bytes=123-246']);
        $this->dm->clear();
        $mmobj = $this->mmobjRepo->find($mmobj->getId());
        $this->assertEquals(1, $mmobj->getNumview());
        //Views should work if range = 0
        $this->client->request('GET', $genUrl, [], [], ['HTTP_RANGE' => 'bytes=0-1256']);
        $this->client->request('GET', $genUrl, [], [], ['HTTP_RANGE' => 'bytes=0-']);
        $this->dm->clear();
        $mmobj = $this->mmobjRepo->find($mmobj->getId());
        $this->assertEquals(3, $mmobj->getNumview());
        //Start should also work
        $this->client->request('GET', $genUrl, [], [], ['HTTP_START' => 0]);
        //xTreme case: If either 'start' or 'range' is valid, it adds a numView.
        $this->client->request('GET', $genUrl, [], [], ['HTTP_START' => 1254, 'HTTP_RANGE' => 'bytes=0-1256']);
        $this->client->request('GET', $genUrl, [], [], ['HTTP_START' => 0, 'HTTP_RANGE' => 'bytes=123-1256']);
        $this->client->request('GET', $genUrl, [], [], ['HTTP_START' => 1254, 'HTTP_RANGE' => 'bytes=123-1256']);
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

    public function testGenerateTrackFileUrlBadExt()
    {
        $series = new Series();
        $mmobj = new MultimediaObject();
        $track = new Track();
        $track->setUrl('https://itunesu-assets.itunes.apple.com/apple-assets-us-std-000001/CobaltPublic6/v4/32/30/4a/32304a65-98c0-6098-3d14-9eb527a59895/ce642c0936a07f17d64df621d5eee4dce2f427c48919297a232e784331f541ea-2556284337.m4v?a=v%3D3%26artistId%3D384228265%26podcastId%3D384232270%26podcastName%3DConvex%2BOptimization%2B%2528EE364A%2529%26episodeId%3D1000085092297%26episodeName%3D4.%2BConvex%2BOptimization%2BI%2BLecture%2B4%26episodeKind%3Dmovie%26pageLocation%3Ditc');
        $mmobj->setSeries($series);
        $mmobj->addTrack($track);
        $this->dm->persist($series);
        $this->dm->persist($mmobj);
        $this->dm->flush();

        $genUrl = $this->trackurlService->generateTrackFileUrl($track);

        $genUrlExt = pathinfo(parse_url($genUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        $trackExt = pathinfo(parse_url($track->getUrl(), PHP_URL_PATH), PATHINFO_EXTENSION);
        $this->assertEquals($trackExt, $genUrlExt);
    }
}
