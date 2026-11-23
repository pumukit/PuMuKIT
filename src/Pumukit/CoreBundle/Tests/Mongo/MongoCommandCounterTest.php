<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Mongo;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Series;

/**
 * @internal
 */
#[CoversNothing]
final class MongoCommandCounterTest extends PumukitTestCase
{
    public function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        parent::setUp();
    }

    public function testItCountsTheCommandsSentWhileItRuns(): void
    {
        $repository = $this->dm->getRepository(Series::class);

        $counter = MongoCommandCounter::start();
        $repository->findBy([]);
        $repository->createQueryBuilder()->count()->getQuery()->execute();
        $counter->stop();
        $repository->findBy([]);

        self::assertSame(2, $counter->count());
        self::assertSame(1, $counter->count('find', 'Series'));
        self::assertSame(1, $counter->count('count'));
    }
}
