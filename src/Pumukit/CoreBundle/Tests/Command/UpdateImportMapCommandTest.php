<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\Command\UpdateImportMapCommand;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntries;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntry;
use Symfony\Component\AssetMapper\ImportMap\ImportMapManager;
use Symfony\Component\AssetMapper\ImportMap\ImportMapType;
use Symfony\Component\AssetMapper\ImportMap\ImportMapUpdateChecker;
use Symfony\Component\AssetMapper\ImportMap\JavaScriptImport;
use Symfony\Component\AssetMapper\ImportMap\PackageRequireOptions;
use Symfony\Component\AssetMapper\ImportMap\PackageUpdateInfo;
use Symfony\Component\AssetMapper\ImportMap\RemotePackageStorage;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversNothing]
class UpdateImportMapCommandTest extends TestCase
{
    private string $tmpDir;

    private array $versions;

    private array $installed;

    private array $required = [];

    private array $removed = [];

    public function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/pumukit-importmap-'.uniqid('', true);
        mkdir($this->tmpDir);

        $this->versions = [
            '@uppy/core' => '6.2.0',
            '@uppy/core/dist/style.min.css' => '6.2.0',
            'preact' => '10.29.8',
            'p-queue' => '9.3.3',
            'old-dependency' => '1.0.0',
            'chart.js' => '4.5.1',
            'chartjs-adapter-date-fns' => '3.0.0',
            'date-fns' => '4.1.0',
        ];
        $this->installed = [
            '@uppy/core' => ['version' => '6.2.0', 'dependencies' => ['preact', 'p-queue', 'old-dependency']],
            'chartjs-adapter-date-fns' => ['dependencies' => ['chart.js', 'date-fns']],
        ];
        $this->writeInstalled();
    }

    public function tearDown(): void
    {
        unlink($this->tmpDir.'/installed.php');
        rmdir($this->tmpDir);
    }

    public function testDryRunListsThePackagesThatPumukitUsesWithTheirMajorConstraint(): void
    {
        $tester = new CommandTester($this->command());
        $tester->execute(['--dry-run' => true]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('@uppy/core ^6.2.0', $display);
        $this->assertStringContainsString('@uppy/core/dist/style.min.css ^6.2.0', $display);
        $this->assertStringContainsString('chart.js ^4.5.1', $display);
        $this->assertStringContainsString('chartjs-adapter-date-fns ^3.0.0', $display);
        $this->assertStringNotContainsString('preact', $display);
        $this->assertStringNotContainsString('date-fns ^', str_replace('chartjs-adapter-date-fns ^', '', $display));
        $this->assertSame([], $this->required);
    }

    public function testIgnoresTheDependenciesOfPackagesThatAreNoLongerInTheImportmap(): void
    {
        $this->versions['lonely'] = '1.2.0';
        $this->installed['removed-package'] = ['version' => '2.0.0', 'dependencies' => ['lonely']];
        $this->writeInstalled();

        $tester = new CommandTester($this->command());
        $tester->execute(['--dry-run' => true]);

        $this->assertStringContainsString('lonely ^1.2.0', $tester->getDisplay());
    }

    public function testUpdatesWithinTheMajorRestoresDowngradesAndRemovesUnusedDependencies(): void
    {
        $tester = new CommandTester($this->command());
        $tester->execute([]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $this->assertSame(
            ['@uppy/core' => '^6.2.0', '@uppy/core/dist/style.min.css' => '^6.2.0', 'chart.js' => '^4.5.1', 'chartjs-adapter-date-fns' => '^3.0.0'],
            $this->required[0]
        );
        $this->assertSame(['date-fns' => '^4.1.0'], $this->required[1]);
        $this->assertCount(2, $this->required);
        $this->assertSame(['old-dependency'], $this->removed);

        $this->assertMatchesRegularExpression('/@uppy\/core\s+\|\s+6\.2\.0\s+\|\s+6\.3\.0/', $display);
        $this->assertMatchesRegularExpression('/p-queue\s+\|\s+9\.3\.3\s+\|\s+10\.0\.0\s+\|\s+major/', $display);
        $this->assertMatchesRegularExpression('/date-fns\s+\|\s+4\.1\.0\s+\|\s+4\.4\.0/', $display);
        $this->assertMatchesRegularExpression('/old-dependency\s+\|\s+1\.0\.0\s+\|\s+removed/', $display);
        $this->assertStringContainsString('p-queue changed its major version as a dependency', $display);
        $this->assertStringContainsString('New major version, update it by hand: chart.js 4.6.1 → 5.0.0', $display);
    }

    public function testReportsThatEverythingIsUpToDate(): void
    {
        $tester = new CommandTester($this->command(false));
        $tester->execute([]);

        $this->assertStringContainsString('already up to date within their major versions', $tester->getDisplay());
        $this->assertSame([], $this->removed);
    }

    private function command(bool $changes = true): UpdateImportMapCommand
    {
        $configReader = $this->createStub(ImportMapConfigReader::class);
        $configReader->method('getEntries')->willReturnCallback(fn () => $this->entries());

        $manager = $this->createStub(ImportMapManager::class);
        $manager->method('require')->willReturnCallback(function (array $packages) use ($changes) {
            $this->required[] = array_combine(
                array_map(static fn (PackageRequireOptions $package) => $package->importName, $packages),
                array_map(static fn (PackageRequireOptions $package) => $package->versionConstraint, $packages)
            );
            if (!$changes) {
                return [];
            }
            if (1 === \count($this->required)) {
                $this->versions = array_merge($this->versions, ['@uppy/core' => '6.3.0', '@uppy/core/dist/style.min.css' => '6.3.0', 'p-queue' => '10.0.0', 'chart.js' => '4.6.1', 'date-fns' => '2.29.3']);
                $this->installed['@uppy/core']['dependencies'] = ['preact', 'p-queue'];
                $this->writeInstalled();
            } else {
                $this->versions['date-fns'] = '4.4.0';
            }

            return [];
        });
        $manager->method('remove')->willReturnCallback(function (array $packages): void {
            $this->removed = $packages;
            $this->versions = array_diff_key($this->versions, array_flip($packages));
        });

        $storage = $this->createStub(RemotePackageStorage::class);
        $storage->method('getStorageDir')->willReturn($this->tmpDir);

        $updateChecker = $this->createStub(ImportMapUpdateChecker::class);
        $updateChecker->method('getAvailableUpdates')->willReturn([
            'chart.js' => new PackageUpdateInfo('chart.js', '4.6.1', '5.0.0', PackageUpdateInfo::UPDATE_TYPE_MAJOR),
            '@uppy/core' => new PackageUpdateInfo('@uppy/core', '6.3.0', '6.3.0', PackageUpdateInfo::UPDATE_TYPE_UP_TO_DATE),
        ]);

        $assetMapper = $this->createStub(AssetMapperInterface::class);
        $assetMapper->method('allAssets')->willReturn([
            new MappedAsset('controllers/stats_controller.js', javaScriptImports: [new JavaScriptImport('chart.js', 'vendor/chart.js/chart.js.index.js', '/vendor/chart.js.index.js')]),
            new MappedAsset('vendor/chartjs-adapter-date-fns/index.js', isVendor: true, javaScriptImports: [new JavaScriptImport('date-fns', 'vendor/date-fns/index.js', '/vendor/date-fns.js')]),
        ]);

        return new UpdateImportMapCommand($configReader, $manager, $storage, $updateChecker, $assetMapper);
    }

    private function entries(): ImportMapEntries
    {
        $entries = [ImportMapEntry::createLocal('app', ImportMapType::JS, 'assets/app.js', true)];
        foreach ($this->versions as $importName => $version) {
            $type = str_ends_with($importName, '.css') ? ImportMapType::CSS : ImportMapType::JS;
            $entries[] = ImportMapEntry::createRemote($importName, $type, '/vendor/'.$importName, $version, $importName, false);
        }

        return new ImportMapEntries($entries);
    }

    private function writeInstalled(): void
    {
        file_put_contents($this->tmpDir.'/installed.php', '<?php return '.var_export($this->installed, true).';');
    }
}
