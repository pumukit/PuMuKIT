<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\Command\VendorOutdatedCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @internal
 */
#[CoversNothing]
class VendorOutdatedCommandTest extends TestCase
{
    private string $tmpDir;

    public function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/pumukit-vendor-'.uniqid('', true);
        mkdir($this->tmpDir.'/jquery', 0777, true);
        mkdir($this->tmpDir.'/bootstrap5/js', 0777, true);
        file_put_contents($this->tmpDir.'/jquery/jquery.min.js', "/*! jQuery v4.0.0 | (c) OpenJS Foundation */\n!function(e,t){}");
        file_put_contents($this->tmpDir.'/bootstrap5/js/bootstrap.bundle.min.js', "/*!\n  * Bootstrap v5.3.8 (https://getbootstrap.com/)\n  */");
    }

    public function tearDown(): void
    {
        unlink($this->tmpDir.'/jquery/jquery.min.js');
        unlink($this->tmpDir.'/bootstrap5/js/bootstrap.bundle.min.js');
        rmdir($this->tmpDir.'/jquery');
        rmdir($this->tmpDir.'/bootstrap5/js');
        rmdir($this->tmpDir.'/bootstrap5');
        rmdir($this->tmpDir);
    }

    public function testComparesTheVendorFilesWithNpm(): void
    {
        $requested = [];
        $client = new MockHttpClient(function (string $method, string $url) use (&$requested) {
            $requested[] = $url;
            $metadata = match ($url) {
                'https://registry.npmjs.org/jquery' => ['dist-tags' => ['latest' => '5.0.1'], 'versions' => array_flip(['3.7.1', '4.0.0', '4.1.0-beta.1', '5.0.1'])],
                'https://registry.npmjs.org/bootstrap' => ['dist-tags' => ['latest' => '5.3.9'], 'versions' => array_flip(['5.3.8', '5.3.9', '5.3.10', '4.6.2'])],
                default => ['dist-tags' => ['latest' => '7.3.1'], 'versions' => array_flip(['7.3.1'])],
            };

            return new MockResponse(json_encode($metadata));
        });

        $tester = new CommandTester(new VendorOutdatedCommand($client, $this->tmpDir));
        $tester->execute([]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertContains('https://registry.npmjs.org/@fortawesome%2Ffontawesome-free', $requested);
        $this->assertMatchesRegularExpression('/jquery\s+\|\s+4\.0\.0\s+\|\s+4\.0\.0\s+\|\s+5\.0\.1\s+\|\s+new major version/', $display);
        $this->assertMatchesRegularExpression('/bootstrap\s+\|\s+5\.3\.8\s+\|\s+5\.3\.10\s+\|\s+5\.3\.9\s+\|\s+update available/', $display);
        $this->assertMatchesRegularExpression('/@fortawesome\/fontawesome-free\s+\|\s+unknown\s+\|\s+-\s+\|\s+7\.3\.1\s+\|\s+version not found in the file/', $display);
    }
}
