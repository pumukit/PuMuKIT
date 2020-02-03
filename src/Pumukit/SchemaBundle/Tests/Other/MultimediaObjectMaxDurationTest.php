<?php

namespace Pumukit\SchemaBundle\Tests\Other;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectMaxDurationTest extends PumukitTestCase
{
    private $dm;
    private $repo;
    private $qb;
    private $factoryService;

    public function setUp()
    {
        $this->dm = parent::setUp();
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->dm->close();
        $this->dm = null;
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

        $this->assertEquals($size, $track->getSize());
        $this->assertEquals($duration, $track->getDuration());
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
