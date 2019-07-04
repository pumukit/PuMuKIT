<?php

namespace Pumukit\WorkflowBundle\Tests\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\WorkflowBundle\EventListener\PicExtractorListener;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class PicExtractorListenerTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $logger;
    private $picExtractorListener;
    private $videoPath;
    private $factoryService;
    private $mmsPicService;
    private $picExtractorService;
    private $autoExtractPic = true;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->logger = static::$kernel->getContainer()->get('logger');
        $this->videoPath = realpath(__DIR__.'/../Resources/data/track.mp4');
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->mmsPicService = static::$kernel->getContainer()->get('pumukitschema.mmspic');
        $this->picExtractorService = static::$kernel->getContainer()->get('pumukitencoder.picextractor');

        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([]);
        $this->dm->getDocumentCollection(Series::class)
            ->remove([]);
        $mmsPicService = $this->getMockBuilder('Pumukit\SchemaBundle\Services\MultimediaObjectPicService')
            ->disableOriginalConstructor()
            ->getMock();
        $mmsPicService->expects($this->any())
            ->method('addPicFile')
            ->will($this->returnValue('multimedia object'));
        $picExtractorService = $this->getMockBuilder('Pumukit\EncoderBundle\Services\PicExtractorService')
            ->disableOriginalConstructor()
            ->getMock();
        $picExtractorService->expects($this->any())
            ->method('extractPic')
            ->will($this->returnValue('success'));
        $this->picExtractorListener = new PicExtractorListener($this->dm, $mmsPicService, $picExtractorService, $this->logger, $this->autoExtractPic);
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->logger = null;
        $this->videoPath = null;
        $this->factoryService = null;
        $this->mmsPicService = null;
        $this->picExtractorService = null;
        $this->picExtractorListener = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGeneratePicFromVideo()
    {
        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $track = new Track();
        $track->addTag('master');
        $track->setPath($this->videoPath);
        $track->setOnlyAudio(false);
        $track->setWidth(640);
        $track->setHeight(480);

        $mm->addTrack($track);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->getPics()->isEmpty());
        $this->assertEquals(0, count($mm->getPics()->toArray()));
        $this->assertTrue($this->invokeMethod($this->picExtractorListener, 'generatePic', [$mm, $track]));

        $pic = new Pic();
        $mm->addPic($pic);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($mm->getPics()->isEmpty());
        $this->assertEquals(1, count($mm->getPics()->toArray()));
        $this->assertFalse($this->invokeMethod($this->picExtractorListener, 'generatePic', [$mm, $track]));
    }

    public function testAddDefaultAudioPic()
    {
        $this->markTestSkipped('S');

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $track = new Track();
        $track->addTag('master');
        $track->setPath($this->videoPath);
        $track->setOnlyAudio(true);
        $track->setWidth(640);
        $track->setHeight(480);

        $mm->addTrack($track);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->getPics()->isEmpty());
        $this->assertEquals(0, count($mm->getPics()->toArray()));

        $this->assertTrue($this->invokeMethod($this->picExtractorListener, 'generatePic', [$mm, $track]));

        $pic = new Pic();
        $mm->addPic($pic);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($mm->getPics()->isEmpty());
        $this->assertEquals(1, count($mm->getPics()->toArray()));
        $this->assertFalse($this->invokeMethod($this->picExtractorListener, 'generatePic', [$mm, $track]));
    }

    public function testPicExtractorVideoError()
    {
        $mmsPicService = $this->getMockBuilder('Pumukit\SchemaBundle\Services\MultimediaObjectPicService')
            ->disableOriginalConstructor()
            ->getMock();
        $mmsPicService->expects($this->any())
            ->method('addPicFile')
            ->will($this->returnValue('multimedia object'));
        $picExtractorService = $this->getMockBuilder('Pumukit\EncoderBundle\Services\PicExtractorService')
            ->disableOriginalConstructor()
            ->getMock();
        $picExtractorService->expects($this->any())
            ->method('extractPic')
            ->will($this->returnValue('Error'));
        $picExtractorListener = new PicExtractorListener($this->dm, $mmsPicService, $picExtractorService, $this->logger, $this->autoExtractPic);

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $track = new Track();
        $track->addTag('master');
        $track->setPath($this->videoPath);
        $track->setOnlyAudio(false);
        $track->setWidth(640);
        $track->setHeight(480);

        $mm->addTrack($track);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->getPics()->isEmpty());
        $this->assertEquals(0, count($mm->getPics()->toArray()));
        $this->assertFalse($this->invokeMethod($picExtractorListener, 'generatePic', [$mm, $track]));
    }

    public function testPicExtractorAudioError()
    {
        $this->markTestSkipped('S');

        $mmsPicService = $this->getMockBuilder('Pumukit\SchemaBundle\Services\MultimediaObjectPicService')
            ->disableOriginalConstructor()
            ->getMock();
        $mmsPicService->expects($this->any())
            ->method('addPicFile')
            ->will($this->returnValue(null));
        $picExtractorService = $this->getMockBuilder('Pumukit\EncoderBundle\Services\PicExtractorService')
            ->disableOriginalConstructor()
            ->getMock();
        $picExtractorService->expects($this->any())
            ->method('extractPic')
            ->will($this->returnValue('success'));
        $picExtractorListener = new PicExtractorListener($this->dm, $mmsPicService, $picExtractorService, $this->logger, $this->autoExtractPic);

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $track = new Track();
        $track->addTag('master');
        $track->setPath($this->videoPath);
        $track->setOnlyAudio(true);
        $track->setWidth(640);
        $track->setHeight(480);

        $mm->addTrack($track);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->getPics()->isEmpty());
        $this->assertEquals(0, count($mm->getPics()->toArray()));
        $this->assertFalse($this->invokeMethod($picExtractorListener, 'generatePic', [$mm, $track]));
    }

    private function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
