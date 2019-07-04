<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectPicServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $factoryService;
    private $mmsPicService;
    private $originalPicPath;
    private $uploadsPath;
    private $picDispatcher;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository(MultimediaObject::class)
        ;
        $this->factoryService = static::$kernel->getContainer()
            ->get('pumukitschema.factory')
        ;
        $this->mmsPicService = static::$kernel->getContainer()
            ->get('pumukitschema.mmspic')
        ;
        $this->picDispatcher = static::$kernel->getContainer()
            ->get('pumukitschema.pic_dispatcher')
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
        $this->mmsPicService = null;
        $this->picDispatcher = null;
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

        $this->assertEquals(4, count($this->mmsPicService->getRecommendedPics($series1)));
    }

    public function testAddPicUrl()
    {
        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getPics()));

        $url = 'http://domain.com/pic.png';

        $mm = $this->mmsPicService->addPicUrl($mm, $url);

        $this->assertEquals(1, count($mm->getPics()));
        $this->assertEquals(1, count($this->repo->find($mm->getId())->getPics()));
    }

    public function testAddPicFile()
    {
        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getPics()));

        $picPath = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'picCopy.png';
        if (copy($this->originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic.png', null, null, null, true);
            $mm = $this->mmsPicService->addPicFile($mm, $picFile);
            $mm = $this->repo->find($mm->getId());

            $this->assertEquals(1, count($mm->getPics()));

            $pic = $mm->getPics()[0];
            $this->assertTrue($mm->containsPic($pic));

            $uploadedPic = '/uploads/pic/series/'.$mm->getSeries()->getId().'/video/'.$mm->getId().DIRECTORY_SEPARATOR.$picFile->getClientOriginalName();
            $this->assertEquals($uploadedPic, $pic->getUrl());
        }

        $this->deleteCreatedFiles();
    }

    public function testRemovePicFromMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $picPath = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'picCopy.png';
        if (copy($this->originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic.png', null, null, null, true);
            $mm = $this->mmsPicService->addPicFile($mm, $picFile);

            $this->assertEquals(1, count($mm->getPics()));

            $pic = $mm->getPics()[0];
            $mm = $this->mmsPicService->removePicFromMultimediaObject($mm, $pic->getId());

            $this->assertEquals(0, count($mm->getPics()));
        }

        $this->deleteCreatedFiles();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage for storing Pics does not exist
     */
    public function testInvalidTargetPath()
    {
        $mmspicService = new MultimediaObjectPicService($this->dm, $this->picDispatcher, '/non/existing/path', '/uploads/pic', true);
    }

    private function deleteCreatedFiles()
    {
        $mmobjs = $this->repo->findAll();

        foreach ($mmobjs as $mm) {
            $mmDir = $this->uploadsPath.DIRECTORY_SEPARATOR.$mm->getId().DIRECTORY_SEPARATOR;

            if (is_dir($mmDir)) {
                $files = glob($mmDir.'*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_writable($file)) {
                        unlink($file);
                    }
                }

                rmdir($mmDir);
            }
        }
    }
}
