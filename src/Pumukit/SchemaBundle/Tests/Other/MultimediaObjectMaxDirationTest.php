<?php

namespace Pumukit\SchemaBundle\Tests\Other;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectMaxDirationTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $qb;
    private $factoryService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');

        //DELETE DATABASE
        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Series::class)
            ->remove([])
        ;
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->factoryService = null;
        gc_collect_cycles();
        parent::tearDown();
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
