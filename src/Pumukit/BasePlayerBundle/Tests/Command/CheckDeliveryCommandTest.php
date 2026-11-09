<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversNothing]
class CheckDeliveryCommandTest extends KernelTestCase
{
    public function testItFailsWhenTheServersDoNotAnswer(): void
    {
        $application = new Application(static::bootKernel());
        $tester = new CommandTester($application->find('pumukit:check:delivery'));

        $tester->execute([
            '--connect-to' => '127.0.0.1:9',
            '--downloads-url' => 'https://dls.example.test/pumukit',
            '--frontend-url' => 'https://tv.example.test',
        ]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Download server serves a signed file', $tester->getDisplay());
        $this->assertStringContainsString('no response', $tester->getDisplay());
        $this->assertStringContainsString('delivery checks failed', $tester->getDisplay());
    }
}
