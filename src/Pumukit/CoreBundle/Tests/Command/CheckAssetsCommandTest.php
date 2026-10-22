<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversNothing]
class CheckAssetsCommandTest extends PumukitTestCase
{
    private string $tmpDir;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir().'/pumukit-assets-'.uniqid('', true);
        mkdir($this->tmpDir);
    }

    public function tearDown(): void
    {
        if (isset($this->tmpDir) && is_dir($this->tmpDir)) {
            array_map('unlink', glob($this->tmpDir.'/*') ?: []);
            rmdir($this->tmpDir);
        }
        parent::tearDown();
    }

    public function testReportsRemovedAssetWithItsReplacement(): void
    {
        file_put_contents(
            $this->tmpDir.'/layout.html.twig',
            "<link href=\"{{ asset('bundles/pumukitcore/vendor/bootstrap/css/bootstrap.min.css') }}\">\n<script src=\"{{ asset('bundles/pumukitwebtv/js/jquery.min.js') }}\"></script>\n"
        );

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Missing asset: bundles/pumukitwebtv/js/jquery.min.js in layout.html.twig:2', $display);
        $this->assertStringContainsString('use bundles/pumukitcore/vendor/jquery/jquery.min.js', $display);
        $this->assertStringNotContainsString('bootstrap.min.css', $display);
    }

    public function testReportsAssetOfBundleThatIsNotInstalled(): void
    {
        file_put_contents($this->tmpDir.'/chat.html.twig', "<link href=\"/bundles/pumukitmissing/css/chat.css\">\n");

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Missing asset: bundles/pumukitmissing/css/chat.css in chat.html.twig:1', $tester->getDisplay());
    }

    public function testSucceedsWhenEveryAssetExists(): void
    {
        file_put_contents(
            $this->tmpDir.'/template.html.twig',
            "<script src=\"{{ asset('bundles/pumukitcore/vendor/jquery/jquery.min.js') }}\"></script>\n<img src=\"{{ asset('bundles/pumukitwebtv/images/categories/' ~ name) }}\">\n<base href=\"../bundles/pumukitmissing/\">\n"
        );

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Missing assets found: 0', $tester->getDisplay());
    }

    public function testFailsWhenPathDoesNotExist(): void
    {
        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir.'/missing']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:check:assets');

        return new CommandTester($command);
    }
}
