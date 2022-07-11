<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Services\LegacyEventPicService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 * @coversNothing
 */
class EventPicServiceTest extends PumukitTestCase
{
    private $repo;
    private $eventPicService;
    private $originalPicPath;
    private $uploadsPath;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(Event::class);
        $this->eventPicService = static::$kernel->getContainer()->get('pumukitlive.legacyeventpic');

        $this->originalPicPath = realpath(__DIR__.'/../Resources').'/logo.png';
        $this->uploadsPath = static::$kernel->getContainer()->getParameter('pumukit.uploads_pic_dir');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->eventPicService = null;
        $this->originalPicPath = null;
        $this->uploadsPath = null;
        gc_collect_cycles();
    }

    public function testAddPicUrl()
    {
        $live = $this->createLiveChannel();
        $event = $this->createLiveEvent($live);

        static::assertNull($event->getPic());

        $url = 'http://domain.com/pic.png';

        $event = $this->eventPicService->addPicUrl($event, $url);

        static::assertEquals($url, $event->getPic()->getUrl());
        static::assertEquals($url, $this->repo->find($event->getId())->getPic()->getUrl());
    }

    public function testAddPicFile()
    {
        $live = $this->createLiveChannel();
        $event = $this->createLiveEvent($live);

        static::assertNull($event->getPic());

        $picPath = realpath(__DIR__.'/../Resources').'/picCopy.png';
        if (copy($this->originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic.png', null, null, true);
            $event = $this->eventPicService->addPicFile($event, $picFile);
            $event = $this->repo->find($event->getId());

            $pic = $event->getPic();
            $uploadedPic = '/uploads/pic/'.$event->getId().'/'.$picFile->getClientOriginalName();
            static::assertEquals($uploadedPic, $pic->getUrl());
        }

        $this->deleteCreatedFiles();
    }

    public function testRemovePicFromEvent()
    {
        $live = $this->createLiveChannel();
        $event = $this->createLiveEvent($live);

        static::assertNull($event->getPic());

        $picPath = realpath(__DIR__.'/../Resources').'/picCopy.png';
        if (copy($this->originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic.png', null, null, true);
            $event = $this->eventPicService->addPicFile($event, $picFile);
            $event = $this->repo->find($event->getId());

            $pic = $event->getPic();
            $uploadedPic = '/uploads/pic/'.$event->getId().'/'.$picFile->getClientOriginalName();
            static::assertEquals($uploadedPic, $pic->getUrl());

            $event = $this->eventPicService->removePicFromEvent($event);
            static::assertNull($event->getPic());
        }

        $this->deleteCreatedFiles();
    }

    public function testInvalidTargetPath()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('for storing Pics does not exist');
        $eventPicService = new LegacyEventPicService($this->dm, '/non/existing/path', '/uploads/pic', true);
    }

    private function createLiveChannel()
    {
        $live = new Live();

        $live->setName('Live channel');
        $live->setUrl('rtmpt://streaming.campusdomar.es:80/live');
        $live->setSourceName('stream');
        $live->setBroadcasting(true);
        $live->setLiveType(Live::LIVE_TYPE_WOWZA);
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
