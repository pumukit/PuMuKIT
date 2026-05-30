<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Command;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversNothing
 */
class CreateMMOCommandTest extends PumukitTestCase
{
    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
    }

    public function testNonCloseWriteEventReturnsSuccessAndCreatesNothing(): void
    {
        $tester = $this->commandTester();
        $tester->execute([
            'file' => '/tmp/whatever.mp4',
            'inotify_event' => 'IN_OPEN',
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $count = $this->dm->getRepository(MultimediaObject::class)->createQueryBuilder()
            ->count()
            ->getQuery()
            ->execute()
        ;
        $this->assertSame(0, $count, 'Non-IN_CLOSE_WRITE events must not create multimedia objects');
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:import:inbox');

        return new CommandTester($command);
    }
}
