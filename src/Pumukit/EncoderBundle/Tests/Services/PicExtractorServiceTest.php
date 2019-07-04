<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\EncoderBundle\Services\PicExtractorService;
use Pumukit\InspectionBundle\Utils\TestCommand;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class PicExtractorServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $factory;
    private $picExtractor;
    private $resourcesDir;
    private $targetPath;
    private $targetUrl;
    private $picEventDispatcher;
    private $inspectionService;
    private $mmsPicService;

    public function setUp()
    {
        if (false === TestCommand::commandExists('/usr/local/bin/ffmpeg')) {
            $this->markTestSkipped('PicExtractor test marks as skipped (No ffmpeg).');
        }

        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
        $this->factory = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->picEventDispatcher = static::$kernel->getContainer()->get('pumukitschema.pic_dispatcher');
        $this->inspectionService = static::$kernel->getContainer()->get('pumukit.inspection');
        $this->resourcesDir = realpath(__DIR__.'/../Resources');
        $this->targetPath = $this->resourcesDir;
        $this->targetUrl = '/uploads';

        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
        $this->dm->getDocumentCollection(Series::class)->remove([]);
        $this->dm->flush();

        $mmsPicService = new MultimediaObjectPicService($this->dm, $this->picEventDispatcher, $this->targetPath, $this->targetUrl, false);
        $width = 304;
        $height = 242;
        $command = 'ffmpeg -ss {{ss}} -y -i "{{input}}" -r 1 -vframes 1 -s {{size}} -f image2 "{{output}}"';
        $this->picExtractor = new PicExtractorService($this->dm, $mmsPicService, $width, $height, $this->targetPath, $this->targetUrl, $command);
    }

    public function tearDown()
    {
        if (isset($this->dm)) {
            $this->dm->close();
        }
        $this->dm = null;
        $this->mmobjRepo = null;
        $this->factory = null;
        $this->mmsPicService = null;
        $this->inspectionService = null;
        $this->resourcesDir = null;
        $this->targetPath = null;
        $this->targetUrl = null;
        $this->picExtractor = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testExtractPic()
    {
        $series = $this->factory->createSeries();
        $multimediaObject = $this->factory->createMultimediaObject($series);

        $trackPath = $this->resourcesDir.'/CAMERA.mp4';

        $track = new Track();
        $track->setPath($trackPath);

        $multimediaObject->addTrack($track);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $output = $this->picExtractor->extractPic($multimediaObject, $track, '25%');

        $this->assertStringStartsWith('Captured the FRAME', $output);

        $multimediaObject = $this->mmobjRepo->find($multimediaObject->getId());
        $pic = $multimediaObject->getPics()[0];

        $this->assertNotNull($pic->getWidth());
        $this->assertNotNull($pic->getHeight());

        $this->assertStringStartsWith($this->resourcesDir, $pic->getPath());

        $this->assertStringStartsWith($this->targetUrl, $pic->getUrl());

        $this->deleteCreatedFiles();
    }

    private function deleteCreatedFiles()
    {
        $multimediaObjects = $this->mmobjRepo->findAll();
        $selectedMultimediaObject = null;
        foreach ($multimediaObjects as $multimediaObject) {
            if (!$multimediaObject->isPrototype()) {
                $selectedMultimediaObject = $multimediaObject;
                break;
            }
        }
        $dirSeries = $this->resourcesDir.'/series/';
        $dirSeriesId = $dirSeries.$selectedMultimediaObject->getSeries()->getId().'/';
        $dirVideo = $dirSeriesId.'video/';
        $dirMmId = $dirVideo.$selectedMultimediaObject->getId().'/';
        $files = glob($dirMmId.'*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_writable($file)) {
                unlink($file);
            }
        }

        $fs = new Filesystem();
        $fs->remove([$dirMmId, $dirVideo, $dirSeriesId, $dirSeries]);
    }
}
