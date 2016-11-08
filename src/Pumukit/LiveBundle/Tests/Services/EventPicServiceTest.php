<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Pumukit\LiveBundle\Services\EventPicService;
use Pumukit\LiveBundle\Document\Live;
use Pumukit\LiveBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Pic;

class EventPicServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $eventPicService;
    private $originalPicPath;
    private $uploadsPath;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository('PumukitLiveBundle:Event');
        $this->eventPicService = static::$kernel->getContainer()->get('pumukitlive.eventpic');

        $this->originalPicPath = realpath(__DIR__.'/../Resources').'/logo.png';
        $this->uploadsPath = realpath(__DIR__.'/../../../../../web/uploads/pic');

        $this->dm->getDocumentCollection('PumukitLiveBundle:Live')->remove(array());
        $this->dm->getDocumentCollection('PumukitLiveBundle:Event')->remove(array());
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->eventPicService = null;
        $this->originalPicPath = null;
        $this->uploadsPath = null;
        gc_collect_cycles();
        parent::tearDown();
    }



    public function testAddPicUrl()
    {
        $live = $this->createLiveChannel();
        $event = $this->createLiveEvent($live);

        $this->assertNull($event->getPic());

        $url = 'http://domain.com/pic.png';

        $event = $this->eventPicService->addPicUrl($event, $url);

        $this->assertEquals($url, $event->getPic()->getUrl());
        $this->assertEquals($url, $this->repo->find($event->getId())->getPic()->getUrl());
    }

    public function testAddPicFile()
    {
        $live = $this->createLiveChannel();
        $event = $this->createLiveEvent($live);

        $this->assertNull($event->getPic());

        $picPath = realpath(__DIR__.'/../Resources').'/picCopy.png';
        if (copy($this->originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic.png', null, null, null, true);
            $event = $this->eventPicService->addPicFile($event, $picFile);
            $event = $this->repo->find($event->getId());

            $pic = $event->getPic();
            $uploadedPic = '/uploads/pic/'.$event->getId().'/'.$picFile->getClientOriginalName();
            $this->assertEquals($uploadedPic, $pic->getUrl());
        }

        $this->deleteCreatedFiles();
    }

    public function testRemovePicFromEvent()
    {
        $live = $this->createLiveChannel();
        $event = $this->createLiveEvent($live);

        $this->assertNull($event->getPic());

        $picPath = realpath(__DIR__.'/../Resources').'/picCopy.png';
        if (copy($this->originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic.png', null, null, null, true);
            $event = $this->eventPicService->addPicFile($event, $picFile);
            $event = $this->repo->find($event->getId());

            $pic = $event->getPic();
            $uploadedPic = '/uploads/pic/'.$event->getId().'/'.$picFile->getClientOriginalName();
            $this->assertEquals($uploadedPic, $pic->getUrl());

            $event = $this->eventPicService->removePicFromEvent($event);
            $this->assertNull($event->getPic());
        }

        $this->deleteCreatedFiles();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage for storing Pics does not exist
     */
    public function testInvalidTargetPath()
    {
        $eventPicService = new EventPicService($this->dm, "/non/existing/path", "/uploads/pic", true);
    }

    private function createLiveChannel()
    {
        $live = new Live();

        $live->setName('Live channel');
        $live->setUrl('rtmpt://streaming.campusdomar.es:80/live');
        $live->setSourceName('stream');
        $live->setBroadcasting(true);
        $live->setLiveType(Live::LIVE_TYPE_FMS);
        $live->setIpSource('*');

        $this->dm->persist($live);
        $this->dm->flush();

        return $live;
    }

    private function createLiveEvent($live)
    {
        $event = new Event();

        $event->setLive($live);
        $event->setName('Live Event');
        $event->setDate(new \DateTime('now'));
        $event->setDuration(60);
        $event->setDisplay(true);

        $this->dm->persist($event);
        $this->dm->flush();

        return $event;
    }

    private function deleteCreatedFiles()
    {
        $events = $this->repo->findAll();

        foreach ($events as $event) {
            $eventDir = $this->uploadsPath.'/'.$event->getId().'/';

            if (is_dir($eventDir)) {
                $files = glob($eventDir.'*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_writable($file)) {
                        unlink($file);
                    }
                }

                rmdir($eventDir);
            }
        }
    }
}
