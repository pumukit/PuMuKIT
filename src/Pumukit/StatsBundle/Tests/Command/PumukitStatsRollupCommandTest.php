<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\StatsBundle\Document\ViewsDailyStats;
use Pumukit\StatsBundle\Document\ViewsLog;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversNothing]
final class PumukitStatsRollupCommandTest extends PumukitTestCase
{
    public function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        parent::setUp();
    }

    public function testItBuildsTheDaysOfViewsLogAndContinuesFromTheLastOne(): void
    {
        $factory = self::getContainer()->get(FactoryService::class);
        $multimediaObject = $factory->createMultimediaObject($factory->createSeries());
        foreach (['-5 days 10:00', '-5 days 11:00', '-3 days 09:00', '-1 day 20:00'] as $when) {
            $log = new ViewsLog('/', '203.0.113.0', 'Firefox', '', $multimediaObject->getId(), $multimediaObject->getSeries()->getId(), null);
            $log->setDate(new \DateTime($when));
            $this->dm->persist($log);
        }
        $this->dm->flush();

        $tester = new CommandTester((new Application(self::$kernel))->find('pumukit:stats:rollup'));
        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString('4 views built', $tester->getDisplay());

        $stats = $this->dm->getDocumentCollection(ViewsDailyStats::class);
        $this->assertSame(3, $stats->countDocuments());
        $this->assertSame(1, $stats->findOne(['views' => 2])['uniqueViews']);
        $this->assertSame(['direct' => 2], (array) $stats->findOne(['views' => 2])['sources']);

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString('1 views built', $tester->getDisplay());
        $this->assertSame(3, $stats->countDocuments());
    }

    public function testItRejectsInvalidDates(): void
    {
        $tester = new CommandTester((new Application(self::$kernel))->find('pumukit:stats:rollup'));

        $this->expectException(\InvalidArgumentException::class);
        $tester->execute(['--from' => '2026-02-30']);
    }
}
