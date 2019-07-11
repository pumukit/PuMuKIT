<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\SeriesPicService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 * @coversNothing
 */
class SeriesPicServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $factoryService;
    private $seriesPicService;
    private $mmsPicService;
    private $originalPicPath;
    private $uploadsPath;
    private $seriesDispatcher;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository(Series::class)
        ;
        $this->factoryService = static::$kernel->getContainer()
            ->get('pumukitschema.factory')
        ;
        $this->seriesPicService = static::$kernel->getContainer()
            ->get('pumukitschema.seriespic')
        ;
        $this->mmsPicService = static::$kernel->getContainer()
            ->get('pumukitschema.mmspic')
        ;
        $this->seriesDispatcher = static::$kernel->getContainer()
            ->get('pumukitschema.series_dispatcher')
        ;

        $this->originalPicPath = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'logo.png';
        $this->uploadsPath = realpath(__DIR__.'/../../../../../web/uploads/pic');

        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
        $this->dm->getDocumentCollection(Series::class)->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->factoryService = null;
        $this->seriesPicService = null;
        $this->mmsPicService = null;
        $this->seriesDispatcher = null;
        $this->originalPicPath = null;
        $this->uploadsPath = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetRecommendedPics()
    {
        $pic1 = new Pic();
        $url1 = 'http://domain.com/pic1.png';
        $pic1->setUrl($url1);

        $pic2 = new Pic();
        $url2 = 'http://domain.com/pic2.png';
        $pic2->setUrl($url2);

        $pic3 = new Pic();
        $url3 = 'http://domain.com/pic3.png';
        $pic3->setUrl($url3);

        $pic4 = new Pic();
        $pic4->setUrl($url3);

        $pic5 = new Pic();
        $url5 = 'http://domain.com/pic5.png';
        $pic5->setUrl($url5);

        $this->dm->persist($pic1);
        $this->dm->persist($pic2);
        $this->dm->persist($pic3);
        $this->dm->persist($pic4);
        $this->dm->persist($pic5);
        $this->dm->flush();

        $series1 = $this->factoryService->createSeries();
        $series2 = $this->factoryService->createSeries();

        $series1 = $this->dm->find(Series::class, $series1->getId());
        $series2 = $this->dm->find(Series::class, $series2->getId());

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);

        $mm21 = $this->factoryService->createMultimediaObject($series2);

        $mm11 = $this->mmsPicService->addPicUrl($mm11, $pic1);
        $mm11 = $this->mmsPicService->addPicUrl($mm11, $pic2);
        $mm11 = $this->mmsPicService->addPicUrl($mm11, $pic4);

        $mm12 = $this->mmsPicService->addPicUrl($mm12, $pic3);

        $mm21 = $this->mmsPicService->addPicUrl($mm21, $pic5);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm21);
        $this->dm->flush();

        $this->assertEquals(3, count($this->seriesPicService->getRecommendedPics($series1)));
        $this->assertEquals(1, count($this->seriesPicService->getRecommendedPics($series2)));
    }

    public function testAddPicUrl()
    {
        $series = $this->factoryService->createSeries();

        $this->assertEquals(0, count($series->getPics()));

        $url = 'http://domain.com/pic.png';
        $bannerTargetUrl = 'http://domain.com/banner';

        $series = $this->seriesPicService->addPicUrl($series, $url);

        $this->assertEquals(1, count($series->getPics()));
        $this->assertEquals(1, count($this->repo->findAll()[0]->getPics()));

        $series = $this->seriesPicService->addPicUrl($series, $url, true, $bannerTargetUrl);

        $this->assertEquals(2, count($series->getPics()));
        $this->assertEquals(2, count($this->repo->findAll()[0]->getPics()));
    }

    public function testAddPicFile()
    {
        $series = $this->factoryService->createSeries();

        $this->assertEquals(0, count($series->getPics()));

        $picPath = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'picCopy.png';
        if (copy($this->originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic.png', null, null, null, true);
            $series = $this->seriesPicService->addPicFile($series, $picFile);
            $series = $this->repo->find($series->getId());

            $this->assertEquals(1, count($series->getPics()));

            $pic = $series->getPics()[0];
            $this->assertTrue($series->containsPic($pic));

            $uploadedPic = '/uploads/pic/series/'.$series->getId().DIRECTORY_SEPARATOR.$picFile->getClientOriginalName();
            $this->assertEquals($uploadedPic, $pic->getUrl());
        }

        $picPath = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'picCopy2.png';
        if (copy($this->originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic2.png', null, null, null, true);

            $bannerTargetUrl = 'http://domain.com/banner';
            $series = $this->seriesPicService->addPicFile($series, $picFile, true, $bannerTargetUrl);

            $this->assertEquals(2, count($series->getPics()));
        }

        $this->deleteCreatedFiles();
    }

    public function testRemovePicFromSeries()
    {
        $series = $this->factoryService->createSeries();

        $this->assertEquals(0, count($series->getPics()));

        $pic = new Pic();
        $url = 'http://domain.com/pic.png';
        $pic->setUrl($url);

        $pic->addTag('tag1');
        $pic->addTag('tag2');
        $pic->addTag('tag3');
        $pic->addTag('banner');

        $this->dm->persist($pic);
        $this->dm->flush();

        $series->addPic($pic);
        $this->assertEquals(1, count($series->getPics()));

        $series = $this->seriesPicService->removePicFromSeries($series, $pic->getId());
        $this->assertEquals(0, count($series->getPics()));

        $picPath = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'picCopy2.png';
        if (copy($this->originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic2.png', null, null, null, true);

            $bannerTargetUrl = 'http://domain.com/banner';
            $series = $this->seriesPicService->addPicFile($series, $picFile, true, $bannerTargetUrl);
            $series = $this->repo->find($series->getId());

            $this->assertEquals(1, count($series->getPics()));

            $pic = $series->getPics()[0];
            $this->assertTrue($series->containsPic($pic));

            $series = $this->seriesPicService->removePicFromSeries($series, $pic->getId());
            $this->assertEquals(0, count($series->getPics()));
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage for storing Pics does not exist
     */
    public function testInvalidTargetPath()
    {
        $seriespicService = new SeriesPicService($this->dm, $this->seriesDispatcher, ['gl'], '/non/existing/path', '/uploads/pic', true);
    }

    private function deleteCreatedFiles()
    {
        $series = $this->repo->findAll();

        foreach ($series as $oneSeries) {
            $oneSeriesDir = $this->uploadsPath.DIRECTORY_SEPARATOR.$oneSeries->getId().DIRECTORY_SEPARATOR;

            if (is_dir($oneSeriesDir)) {
                $files = glob($oneSeriesDir.'*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_writable($file)) {
                        unlink($file);
                    }
                }

                rmdir($oneSeriesDir);
            }
        }
    }
}
