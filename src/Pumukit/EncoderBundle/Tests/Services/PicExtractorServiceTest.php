<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\EncoderBundle\Services\PicExtractorService;
use Pumukit\InspectionBundle\Utils\TestCommand;

class PicExtractorServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $factory;
    private $picExtractor;
    private $resourcesDir;
    private $targetPath;
    private $targetUrl;

    public function setUp()
    {
        if (TestCommand::commandExists('avconv') == false) {
            $this->markTestSkipped('PicExtractor test marks as skipped (No avconv).');
        }

        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->factory = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->mmsPicService = static::$kernel->getContainer()->get('pumukitschema.mmspic');
        $this->inspectionService = static::$kernel->getContainer()->get('pumukit.inspection');
        $this->resourcesDir = realpath(__DIR__.'/../Resources');
        $this->targetPath = $this->resourcesDir;
        $this->targetUrl = '/uploads';

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->flush();

        $width = 304;
        $height = 242;
        $command = 'avconv -ss {{ss}} -y -i "{{input}}" -r 1 -vframes 1 -s {{size}} -f image2 "{{output}}"';
        $this->picExtractor = new PicExtractorService($this->dm, $this->mmsPicService, $width, $height, $this->targetPath, $this->targetUrl, $command);
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

        $picPath = $this->resourcesDir.'/series/'.$multimediaObject->getSeries()->getId().'/video/'.$multimediaObject->getId().'/';
        $this->assertStringStartsWith($picPath, $pic->getPath());

        $picUrl = $this->targetUrl.'/series/'.$multimediaObject->getSeries()->getId().'/video/'.$multimediaObject->getId().'/';
        $this->assertStringStartsWith($picUrl, $pic->getUrl());

        $this->deleteCreatedFiles();
    }

    private function deleteCreatedFiles()
    {
        $multimediaObjects = $this->mmobjRepo->findAll();
        foreach ($multimediaObjects as $multimediaObject) {
            if (!$multimediaObject->isPrototype()) {
                break;
            }
        }
        $dirSeries = $this->resourcesDir.'/series/';
        $dirSeriesId = $dirSeries.$multimediaObject->getSeries()->getId().'/';
        $dirVideo = $dirSeriesId.'video/';
        $dirMmId = $dirVideo.$multimediaObject->getId().'/';
        $files = glob($dirMmId.'*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_writable($file)) {
                unlink($file);
            }
        }

        $fs = new Filesystem();
        $fs->remove(array($dirMmId, $dirVideo, $dirSeriesId, $dirSeries));
    }
}
