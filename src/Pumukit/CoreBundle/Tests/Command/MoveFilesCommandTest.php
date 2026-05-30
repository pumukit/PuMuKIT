<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Command;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversNothing
 */
class MoveFilesCommandTest extends PumukitTestCase
{
    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
    }

    public function testNegativeLimitIsRejected(): void
    {
        $tmpOrigin = sys_get_temp_dir().'/pumukit-move-origin-'.uniqid('', true);
        $tmpDestiny = sys_get_temp_dir().'/pumukit-move-destiny-'.uniqid('', true);
        mkdir($tmpOrigin);
        mkdir($tmpDestiny);

        try {
            $tester = $this->commandTester();

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('--limit must be zero or a positive integer');

            $tester->execute([
                '--origin' => $tmpOrigin,
                '--destiny' => $tmpDestiny,
                '--limit' => '-1',
            ]);
        } finally {
            rmdir($tmpOrigin);
            rmdir($tmpDestiny);
        }
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:move:files');

        return new CommandTester($command);
    }
}
