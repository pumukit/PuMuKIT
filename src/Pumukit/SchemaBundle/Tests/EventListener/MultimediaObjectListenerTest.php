<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\EventListener;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\EventListener\MultimediaObjectListener;
use Pumukit\SchemaBundle\Services\TextIndexService;
use Pumukit\SchemaBundle\Services\TrackService;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @coversNothing
 */
class MultimediaObjectListenerTest extends PumukitTestCase
{
    private $listener;
    private $trackDispatcher;
    private $trackService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $dispatcher = new EventDispatcher();
        $this->listener = new MultimediaObjectListener($this->dm, new TextIndexService());
        $dispatcher->addListener('multimediaobject.update', [$this->listener, 'postUpdate']);
        $this->trackDispatcher = static::$kernel->getContainer()->get('pumukitschema.track_dispatcher');

        $this->trackService = new TrackService($this->dm, $this->trackDispatcher, null, true);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->listener = null;
        $this->trackDispatcher = null;
        $this->trackService = null;
        gc_collect_cycles();
    }

    public function testPostUpdate()
    {
        // MULTIMEDIA OBJECT TEST
        // TEST IS ONLY AUDIO
        $mm = new MultimediaObject();
        $mm->setNumericalID(1);

        $t1 = new Track();
        $t1->setOnlyAudio(true);
        $t2 = new Track();
        $t2->setOnlyAudio(true);
        $t3 = new Track();
        $t3->setOnlyAudio(true);
        $t4 = new Track();
        $t4->setOnlyAudio(true);
        $t5 = new Track();
        $t5->setOnlyAudio(true);
        $t5->addTag('master');

        $this->trackService->addTrackToMultimediaObject($mm, $t1, false);
        $this->trackService->addTrackToMultimediaObject($mm, $t2, false);
        $this->trackService->addTrackToMultimediaObject($mm, $t3, false);
        $this->trackService->addTrackToMultimediaObject($mm, $t4, false);
        $this->trackService->addTrackToMultimediaObject($mm, $t5, true);

        static::assertTrue($mm->isOnlyAudio());

        $t5->setOnlyAudio(false);

        $this->trackService->updateTrackInMultimediaObject($mm, $t5);

        static::assertFalse($mm->isOnlyAudio());

        // TEST GET MASTER
        $mm = new MultimediaObject();
        $mm->setNumericalID(2);
        $track3 = new Track();
        $track3->addTag('master');
        $track3->setOnlyAudio(false);
        $track2 = new Track();
        $track2->setOnlyAudio(false);
        $track1 = new Track();
        $track1->setOnlyAudio(true);

        static::assertEquals(null, $mm->getMaster());
        $this->trackService->addTrackToMultimediaObject($mm, $track1, true);
        static::assertEquals($track1, $mm->getMaster());
        static::assertEquals(null, $mm->getMaster(false));
        $this->trackService->addTrackToMultimediaObject($mm, $track2, true);
        static::assertEquals($track2, $mm->getMaster());
        static::assertEquals(null, $mm->getMaster(false));
        $this->trackService->addTrackToMultimediaObject($mm, $track3, true);
        static::assertEquals($track3, $mm->getMaster());
        static::assertEquals($track3, $mm->getMaster(false));
    }
}
