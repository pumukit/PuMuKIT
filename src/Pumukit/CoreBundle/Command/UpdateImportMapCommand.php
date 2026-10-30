<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntry;
use Symfony\Component\AssetMapper\ImportMap\ImportMapManager;
use Symfony\Component\AssetMapper\ImportMap\ImportMapUpdateChecker;
use Symfony\Component\AssetMapper\ImportMap\PackageRequireOptions;
use Symfony\Component\AssetMapper\ImportMap\PackageUpdateInfo;
use Symfony\Component\AssetMapper\ImportMap\RemotePackageStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class UpdateImportMapCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'asset_mapper.importmap.config_reader')]
        private readonly ImportMapConfigReader $configReader,
        #[Autowire(service: 'asset_mapper.importmap.manager')]
        private readonly ImportMapManager $importMapManager,
        #[Autowire(service: 'asset_mapper.importmap.remote_package_storage')]
        private readonly RemotePackageStorage $packageStorage,
        #[Autowire(service: 'asset_mapper.importmap.update_checker')]
        private readonly ImportMapUpdateChecker $updateChecker,
        private readonly AssetMapperInterface $assetMapper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:importmap:update')
            ->setDescription('Update the importmap packages that PuMuKIT uses to their latest version within the same major version')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List the packages that would be updated without changing the importmap')
            ->setHelp(
                <<<'EOT'
The <info>pumukit:importmap:update</info> command updates the remote packages of the importmap
without breaking the packages that depend on them, unlike <comment>importmap:update</comment>, which
moves every package to its latest version.

It requires again, in a single call, the packages that the PuMuKIT code imports and the ones
that no other package depends on, with a <comment>^current-version</comment> constraint, so they stay in
their major version. jsDelivr resolves their dependencies within the ranges that each package
declares. The dependencies that no package uses afterwards are removed.

It reports the dependencies that changed their major version and the new major versions of
the updated packages, which are left for a manual update.

    Example:
        php bin/console pumukit:importmap:update
        php bin/console pumukit:importmap:update --dry-run
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $before = $this->remoteEntries();
        $roots = $this->roots($before);

        if ($input->getOption('dry-run')) {
            foreach ($roots as $entry) {
                $output->writeln(\sprintf('<info>%s</info> ^%s', $entry->importName, $entry->version));
            }

            return Command::SUCCESS;
        }

        $this->importMapManager->require($this->requireOptions($roots));

        $required = $this->remoteEntries();
        $downgraded = array_filter(
            array_intersect_key($before, $required),
            static fn (ImportMapEntry $entry) => version_compare($required[$entry->importName]->version, $entry->version, '<')
        );
        if ($downgraded) {
            $this->importMapManager->require($this->requireOptions($downgraded));
        }

        $orphans = array_keys(array_diff_key($this->remoteEntries(), $roots, $this->dependencies()));
        if ($orphans) {
            $this->importMapManager->remove($orphans);
        }

        $this->report($output, $before, $this->remoteEntries(), $orphans);
        $this->reportMajorUpdates($output, array_keys($roots));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, ImportMapEntry> $entries
     *
     * @return PackageRequireOptions[]
     */
    private function requireOptions(array $entries): array
    {
        return array_values(array_map(
            static fn (ImportMapEntry $entry) => new PackageRequireOptions($entry->packageModuleSpecifier, '^'.$entry->version, $entry->importName, null, $entry->isEntrypoint),
            $entries
        ));
    }

    /**
     * @return array<string, ImportMapEntry>
     */
    private function remoteEntries(): array
    {
        $entries = [];
        foreach ($this->configReader->getEntries() as $entry) {
            if ($entry->isRemotePackage()) {
                $entries[$entry->importName] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @param array<string, ImportMapEntry> $entries
     *
     * @return array<string, ImportMapEntry>
     */
    private function roots(array $entries): array
    {
        $dependencies = $this->dependencies();
        $imported = $this->localImports();

        return array_filter($entries, static fn (ImportMapEntry $entry) => isset($imported[$entry->importName]) || !isset($dependencies[$entry->importName]));
    }

    /**
     * @return array<string, true>
     */
    private function dependencies(): array
    {
        $file = $this->packageStorage->getStorageDir().'/installed.php';
        $installed = is_file($file) ? (static fn (string $file) => require $file)($file) : [];

        $dependencies = [];
        foreach ($installed as $package) {
            foreach ($package['dependencies'] ?? [] as $dependency) {
                $dependencies[$dependency] = true;
            }
        }

        return $dependencies;
    }

    /**
     * @return array<string, true>
     */
    private function localImports(): array
    {
        $imported = [];
        foreach ($this->assetMapper->allAssets() as $asset) {
            if ($asset->isVendor) {
                continue;
            }
            foreach ($asset->getJavaScriptImports() as $import) {
                $imported[$import->importName] = true;
            }
        }

        return $imported;
    }

    /**
     * @param array<string, ImportMapEntry> $before
     * @param array<string, ImportMapEntry> $after
     * @param string[]                      $removed
     */
    private function report(OutputInterface $output, array $before, array $after, array $removed): void
    {
        $rows = [];
        foreach ($after as $importName => $entry) {
            $previous = $before[$importName]->version ?? null;
            if ($previous === $entry->version) {
                continue;
            }
            $rows[] = [$importName, $previous ?? 'new', $entry->version, $this->isMajorChange($previous, $entry->version) ? 'major' : ''];
        }
        foreach ($removed as $importName) {
            $rows[] = [$importName, $before[$importName]->version ?? '', 'removed', ''];
        }

        if (!$rows) {
            $output->writeln('The importmap packages are already up to date within their major versions.');

            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Package', 'Before', 'After', '']);
        $table->setRows($rows);
        $table->render();

        foreach ($rows as [$importName, , , $major]) {
            if ('major' === $major) {
                $output->writeln(\sprintf('<comment>%s changed its major version as a dependency: review the pages that use it.</comment>', $importName));
            }
        }
    }

    /**
     * @param string[] $roots
     */
    private function reportMajorUpdates(OutputInterface $output, array $roots): void
    {
        $majors = array_filter(
            $this->updateChecker->getAvailableUpdates($roots),
            static fn (PackageUpdateInfo $update) => PackageUpdateInfo::UPDATE_TYPE_MAJOR === $update->updateType
        );

        foreach ($majors as $importName => $update) {
            $output->writeln(\sprintf('New major version, update it by hand: <info>%s</info> %s → %s', $importName, $update->currentVersion, $update->latestVersion));
        }
    }

    private function isMajorChange(?string $previous, string $current): bool
    {
        if (null === $previous) {
            return false;
        }

        [$previousMajor, $previousMinor] = array_pad(explode('.', $previous), 2, '0');
        [$currentMajor, $currentMinor] = array_pad(explode('.', $current), 2, '0');

        return '0' === $previousMajor ? $previousMinor !== $currentMinor || '0' !== $currentMajor : $previousMajor !== $currentMajor;
    }
}
