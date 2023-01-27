<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Other;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;

/**
 * @internal
 *
 * @coversNothing
 */
class MultimediaObjectMaxDurationTest extends PumukitTestCase
{
    private $repo;
    private $factoryService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->factoryService = null;
        gc_collect_cycles();
    }

    public function testMaxDuration()
    {
        $size = 2446644584;
        $duration = 608;

        $mm = $this->createMultimediaObject();
        $track = new Track();
        $mm->addTrack($track);

        $this->dm->persist($mm);
        $this->dm->flush();

        $track->setSize($size);
        $track->setDuration($duration);

        $this->dm->persist($mm);
        $this->dm->flush();

        $id = $mm->getId();
        $trackId = $track->getId();

        $this->dm->clear();

        $mm = $this->repo->find($id);
        $track = $mm->getTrackById($trackId);

        static::assertEquals($size, $track->getSize());
        static::assertEquals($duration, $track->getDuration());
    }

    private function createMultimediaObject()
    {
        $rank = 1;
        $status = MultimediaObject::STATUS_NEW;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $title = 'Title';
        $subtitle = 'Subtitle';
        $description = 'Description';

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $mm->setStatus($status);
        $mm->setRecordDate($record_date);
        $mm->setPublicDate($public_date);
        $mm->setTitle($title);
        $mm->setSubtitle($subtitle);
        $mm->setDescription($description);

        $this->dm->persist($mm);
        $this->dm->flush();

        return $mm;
    }
}
