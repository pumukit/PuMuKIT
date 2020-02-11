<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectDurationServiceTest extends PumukitTestCase
{
    private $mmRepo;
    private $factory;
    private $mmsService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->mmRepo = $this->dm->getRepository(MultimediaObject::class);
        $this->factory = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->mmsService = static::$kernel->getContainer()->get('pumukitschema.mmsduration');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->mmRepo = null;
        $this->factory = null;
        $this->mmsService = null;
        gc_collect_cycles();
    }

    public function testGetDuration()
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);
        $this->dm->persist($mm);
        $this->dm->flush();

        $mm->setDuration(100);

        $duration = $this->mmsService->getMmobjDuration($mm);
        $this->assertEquals(100, $duration);
    }
}
