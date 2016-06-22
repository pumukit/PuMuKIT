<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Services\PicService;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Track;

class PicServiceTest extends WebTestCase
{
    private $dm;
    private $factoryService;
    private $picService;
    private $context;
    private $defaultSeriesPic = '/images/series.jpg';
    private $defaultPlaylistPic = '/images/playlist.jpg';
    private $defaultVideoPic = '/images/video.jpg';
    private $defaultAudioHDPic = '/images/audio_hd.jpg';
    private $defaultAudioSDPic = '/images/audio_sd.jpg';
    private $localhost = 'http://localhost';
    private $webDir;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->context = static::$kernel->getContainer()->get('router.request_context');
        $this->rootDir = static::$kernel->getContainer()->getParameter('kernel.root_dir');
        $this->webDir = realpath($this->rootDir.'/../web/bundles/pumukitschema');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->flush();

        $this->picService = new PicService($this->context, $this->webDir, $this->defaultSeriesPic, $this->defaultPlaylistPic, $this->defaultVideoPic, $this->defaultAudioHDPic, $this->defaultAudioSDPic);
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->factoryService = null;
        $this->context = null;
        $this->rootDir = null;
        $this->webDir = null;
        $this->picService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetFirstUrlPic()
    {
        // SERIES SECTION
        $series = $this->factoryService->createSeries();

        $absolute = false;
        $this->assertEquals($this->defaultSeriesPic, $this->picService->getFirstUrlPic($series, $absolute));

        $absolute = true;
        $this->assertEquals($this->localhost.$this->defaultSeriesPic, $this->picService->getFirstUrlPic($series, $absolute));

        $seriesUrl1 = '/uploads/series1.jpg';
        $seriesPic1 = new Pic();
        $seriesPic1->setUrl($seriesUrl1);

        $series->addPic($seriesPic1);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals($seriesUrl1, $this->picService->getFirstUrlPic($series));

        $seriesUrl2 = '/uploads/series2.jpg';
        $seriesPic2 = new Pic();
        $seriesPic2->setUrl($seriesUrl2);

        $series->addPic($seriesPic2);

        $this->dm->persist($series);
        $this->dm->flush();

        $series->upPicById($seriesPic2->getId());

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals($seriesUrl2, $this->picService->getFirstUrlPic($series));

        $mm = $this->factoryService->createMultimediaObject($series);
        $mm->setSeries($series);
        $this->dm->persist($mm);
        $this->dm->flush();

        $track = new Track();
        $track->setOnlyAudio(false);
        $mm->addTrack($track);

        $this->dm->persist($mm);
        $this->dm->flush();

        $absolute = false;
        $this->assertEquals($this->defaultVideoPic, $this->picService->getFirstUrlPic($mm, $absolute));

        $absolute = true;
        $this->assertEquals($this->localhost.$this->defaultVideoPic, $this->picService->getFirstUrlPic($mm, $absolute));

        $track->setOnlyAudio(true);
        $this->dm->persist($mm);
        $this->dm->flush();

        $absolute = false;
        $hd = true;
        $this->assertEquals($this->defaultAudioHDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));
        $hd = false;
        $this->assertEquals($this->defaultAudioSDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));

        $absolute = true;
        $hd = true;
        $this->assertEquals($this->localhost.$this->defaultAudioHDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));
        $hd = false;
        $this->assertEquals($this->localhost.$this->defaultAudioSDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));

        $mmUrl1 = '/uploads/video1.jpg';
        $mmPic1 = new Pic();
        $mmPic1->setUrl($mmUrl1);

        $mm->addPic($mmPic1);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals($mmUrl1, $this->picService->getFirstUrlPic($mm));

        $absolute = true;
        $this->assertEquals($this->localhost.$mmUrl1, $this->picService->getFirstUrlPic($mm, $absolute));

        $mmUrl2 = '/uploads/video2.jpg';
        $mmPic2 = new Pic();
        $mmPic2->setUrl($mmUrl2);

        $mm->addPic($mmPic2);

        $this->dm->persist($mm);

        $mm->upPicById($mmPic2->getId());

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals($mmUrl2, $this->picService->getFirstUrlPic($mm));

        $absolute = true;
        $this->assertEquals($this->localhost.$mmUrl2, $this->picService->getFirstUrlPic($mm, $absolute));
    }

    public function testGetDefaultUrlPicForObject()
    {
        $pic = new Pic();

        $absolute = false;
        $this->assertEquals($this->defaultVideoPic, $this->picService->getDefaultUrlPicForObject($pic, $absolute));

        $absolute = true;
        $this->assertEquals($this->localhost.$this->defaultVideoPic, $this->picService->getDefaultUrlPicForObject($pic, $absolute));
    }

    public function testGetFirstPathPic()
    {
        // SERIES SECTION
        $series = $this->factoryService->createSeries();

        $this->assertEquals($this->webDir.$this->defaultSeriesPic, $this->picService->getFirstPathPic($series));

        $seriesPath1 = $this->webDir.'/uploads/series1.jpg';
        $seriesPic1 = new Pic();
        $seriesPic1->setPath($seriesPath1);

        $series->addPic($seriesPic1);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals($seriesPath1, $this->picService->getFirstPathPic($series));

        $seriesPath2 = $this->webDir.'/uploads/series2.jpg';
        $seriesPic2 = new Pic();
        $seriesPic2->setPath($seriesPath2);

        $series->addPic($seriesPic2);

        $this->dm->persist($series);

        $series->upPicById($seriesPic2->getId());

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals($seriesPath2, $this->picService->getFirstPathPic($series));

        // MULTIMEDIA OBJECT SECTION
        // Workaround for detached Series document
        $this->dm->clear(get_class($series));
        $series = $this->dm->find('PumukitSchemaBundle:Series', $series->getId());

        $mm = $this->factoryService->createMultimediaObject($series);
        $mm->setSeries($series);
        $this->dm->persist($mm);
        $this->dm->flush();

        $track = new Track();
        $track->setOnlyAudio(false);
        $mm->addTrack($track);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals($this->webDir.$this->defaultVideoPic, $this->picService->getFirstPathPic($mm));

        $track->setOnlyAudio(true);
        $this->dm->persist($mm);
        $this->dm->flush();

        $hd = true;
        $this->assertEquals($this->webDir.$this->defaultAudioHDPic, $this->picService->getFirstPathPic($mm, $hd));
        $hd = false;
        $this->assertEquals($this->webDir.$this->defaultAudioSDPic, $this->picService->getFirstPathPic($mm, $hd));

        $mmPath1 = realpath(__DIR__.'/../Resources/images/video_none.jpg');
        $mmPic1 = new Pic();
        $mmPic1->setPath($mmPath1);

        $mm->addPic($mmPic1);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals($mmPath1, $this->picService->getFirstPathPic($mm));

        $mmPath2 = realpath(__DIR__.'/../Resources/images/series_folder.png');
        $mmPic2 = new Pic();
        $mmPic2->setPath($mmPath2);

        $mm->addPic($mmPic2);

        $this->dm->persist($mm);

        $mm->upPicById($mmPic2->getId());

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals($mmPath2, $this->picService->getFirstPathPic($mm));
    }

    public function testGetDefaultPathPicForObject()
    {
        $pic = new Pic();

        $this->assertEquals($this->webDir.$this->defaultVideoPic, $this->picService->getDefaultPathPicForObject($pic));
    }
}
