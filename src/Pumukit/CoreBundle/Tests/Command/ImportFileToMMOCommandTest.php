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
class ImportFileToMMOCommandTest extends PumukitTestCase
{
    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
    }

    public function testNonExistentFileIsRejected(): void
    {
        $tester = $this->commandTester();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Path is not a file/');

        $tester->execute([
            'object' => '5b4dd4c22bb478607d8b456b',
            'file' => '/nonexistent/path/test.mp4',
        ]);
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:import:multimedia:file');

        return new CommandTester($command);
    }
}
