<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Services;

use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Document\ValueObject\Url;
use Pumukit\WebTVBundle\PumukitWebTVBundle;

/**
 * @internal
 *
 * @coversNothing
 */
class TrackUrlServiceTest extends PumukitTestCase
{
    private $client;
    private $trackurlService;
    private $mmobjRepo;
    private $i18nService;

    public function setUp(): void
    {
        parent::setUp();

        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
        $this->trackurlService = static::$kernel->getContainer()->get('pumukit_baseplayer.trackurl');
        $this->i18nService = new i18nService(['en', 'es'], 'en');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->mmobjRepo = null;
        $this->trackurlService = null;
        $this->client = null;
        gc_collect_cycles();
    }

    public function testGenerateTrackFileUrl()
    {
        $track = $this->generateTrackMedia('https://localhost/pumukit.mp4');
        $series = new Series();
        $series->setNumericalID(1);
        $mmobj = new MultimediaObject();
        $mmobj->setNumericalID(1);
        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $tag = new Tag();
        $tag->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $mmobj->addTag($tag);
        $mmobj->setSeries($series);
        $mmobj->addTrack($track);
        $this->dm->persist($series);
        $this->dm->persist($mmobj);
        $this->dm->flush();

        static::assertEquals(0, $mmobj->getNumview());

        $genUrl = $this->trackurlService->generateTrackFileUrl($track);
        $this->client->request('GET', $genUrl);
        // @Route("/trackfile/{id}.{ext}", name="pumukit_trackfile_index" )
        static::assertEquals($genUrl, '/trackfile/'.$track->id().'.mp4');
        static::assertEquals(302, $this->client->getResponse()->getStatusCode());
        static::assertEquals($track->storage()->url()->url(), $this->client->getResponse()->getTargetUrl());
        // Reload mmobj to check for new views.
        $this->dm->clear();
        $mmobj = $this->mmobjRepo->find($mmobj->getId());
        static::assertEquals(1, $mmobj->getNumview());
        $this->client->request('GET', $genUrl, [], [], ['HTTP_RANGE' => 'bytes=123-246']);
        $this->dm->clear();
        $mmobj = $this->mmobjRepo->find($mmobj->getId());
        static::assertEquals(1, $mmobj->getNumview());
        // Views should work if range = 0
        $this->client->request('GET', $genUrl, [], [], ['HTTP_RANGE' => 'bytes=0-1256']);
        $this->client->request('GET', $genUrl, [], [], ['HTTP_RANGE' => 'bytes=0-']);
        $this->dm->clear();
        $mmobj = $this->mmobjRepo->find($mmobj->getId());
        static::assertEquals(3, $mmobj->getNumview());
        // Start should also work
        $this->client->request('GET', $genUrl, [], [], ['HTTP_START' => 0]);
        // xTreme case: If either 'start' or 'range' is valid, it adds a numView.
        $this->client->request('GET', $genUrl, [], [], ['HTTP_START' => 1254, 'HTTP_RANGE' => 'bytes=0-1256']);
        $this->client->request('GET', $genUrl, [], [], ['HTTP_START' => 0, 'HTTP_RANGE' => 'bytes=123-1256']);
        $this->client->request('GET', $genUrl, [], [], ['HTTP_START' => 1254, 'HTTP_RANGE' => 'bytes=123-1256']);
        $this->dm->clear();
        $mmobj = $this->mmobjRepo->find($mmobj->getId());
        static::assertEquals(6, $mmobj->getNumview());
        // With GET params
        $getParams = '?1=2&forcedl=1';
        $genUrl = $this->trackurlService->generateTrackFileUrl($track);
        $this->client->request('GET', $genUrl.$getParams);
        // @Route("/trackfile/{id}.{ext}", name="pumukit_trackfile_index" )
        static::assertEquals($genUrl, '/trackfile/'.$track->id().'.mp4');
        static::assertEquals(302, $this->client->getResponse()->getStatusCode());
        static::assertEquals($track->storage()->url()->url().$getParams, $this->client->getResponse()->getTargetUrl());
    }

    public function testGenerateTrackFileUrlBadExt()
    {
        $series = new Series();
        $series->setNumericalID(1);
        $mmobj = new MultimediaObject();
        $mmobj->setNumericalID(1);
        $track = $this->generateTrackMedia('https://itunesu-assets.itunes.apple.com/apple-assets-us-std-000001/CobaltPublic6/v4/32/30/4a/32304a65-98c0-6098-3d14-9eb527a59895/ce642c0936a07f17d64df621d5eee4dce2f427c48919297a232e784331f541ea-2556284337.m4v?a=v%3D3%26artistId%3D384228265%26podcastId%3D384232270%26podcastName%3DConvex%2BOptimization%2B%2528EE364A%2529%26episodeId%3D1000085092297%26episodeName%3D4.%2BConvex%2BOptimization%2BI%2BLecture%2B4%26episodeKind%3Dmovie%26pageLocation%3Ditc');
        $mmobj->setSeries($series);
        $mmobj->addTrack($track);
        $this->dm->persist($series);
        $this->dm->persist($mmobj);
        $this->dm->flush();

        $genUrl = $this->trackurlService->generateTrackFileUrl($track);

        $genUrlExt = pathinfo(parse_url($genUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        $trackExt = pathinfo(parse_url($track->storage()->url()->url(), PHP_URL_PATH), PATHINFO_EXTENSION);
        static::assertEquals($trackExt, $genUrlExt);
    }

    private function generateTrackMedia(string $url): MediaInterface
    {
        $originalName = 'originalName'.rand();
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['display']);
        $views = 0;
        $url = Url::create($url);
        $path = Path::create('public/storage');
        $storage = Storage::create($url, $path);
        $mediaMetadata = VideoAudio::create('{"format":{"duration":"10.000000"}}');

        return Track::create(
            $originalName,
            $description,
            $language,
            $tags,
            false,
            true,
            $views,
            $storage,
            $mediaMetadata
        );
    }
}
