<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Services\TrackDeliveryService;
use Pumukit\SchemaBundle\Document\Exception\PathException;
use Pumukit\SchemaBundle\Document\MediaType\Media;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'pumukit:hls:move-to-own-directory', description: 'Move the old HLS tracks of a series directory to their own directory')]
final class MoveHlsToOwnDirectoryCommand extends Command
{
    private const BATCH_SIZE = 100;
    private const NAME_PATTERN = '/^[A-Za-z0-9_-]+$/';
    private const REASON_SHARED = 'shared, re-encode';

    private readonly Filesystem $filesystem;
    private string $root = '';

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly TrackDeliveryService $trackDeliveryService,
        #[Autowire('%pumukit.storage_dir%')]
        private readonly string $storageDir
    ) {
        $this->filesystem = new Filesystem();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print what would be moved and change nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $this->root = rtrim((string) realpath($this->storageDir), '/');
        if ('' === $this->root) {
            $io->error('The storage directory does not exist.');

            return Command::FAILURE;
        }

        $directories = $this->collectHlsDirectories();
        $otherFiles = [] === $directories ? [] : $this->collectOtherTrackFiles($directories);

        $moved = 0;
        $shared = 0;
        $skipped = 0;
        foreach ($directories as $directory => $tracks) {
            if (count($tracks) > 1) {
                foreach ($tracks as $track) {
                    $output->writeln(sprintf('%s %s %s %s', $track['mmId'], $track['trackId'], $track['path'], self::REASON_SHARED));
                    ++$shared;
                }

                continue;
            }

            $track = $tracks[0];
            $result = $this->moveTrack($track, $directory, $otherFiles[$directory] ?? [], $dryRun);
            $this->documentManager->clear();
            if (is_string($result)) {
                $output->writeln(sprintf('%s %s %s %s', $track['mmId'], $track['trackId'], $track['path'], $result));
                ++$skipped;

                continue;
            }

            $output->writeln(sprintf('%s %s %s -> %s', $track['mmId'], $track['trackId'], $track['path'], $result['path']));
            ++$moved;
        }

        $summary = sprintf(
            '%s %d tracks, %d in shared directories, %d skipped.',
            $dryRun ? 'Would move' : 'Moved',
            $moved,
            $shared,
            $skipped
        );

        if ($shared + $skipped > 0) {
            $io->error($summary);

            return Command::FAILURE;
        }

        $io->success($summary);

        return Command::SUCCESS;
    }

    private function collectHlsDirectories(): array
    {
        $groups = [];
        $withCandidate = [];
        foreach ($this->tracks() as [$mmId, $track, $path]) {
            if (!$this->isHls($path)) {
                continue;
            }

            $realPath = realpath($path);
            $directory = false === $realPath ? dirname($path) : dirname($realPath);
            $groups[$directory][] = ['mmId' => $mmId, 'trackId' => $track->id(), 'path' => $path];

            if (false !== $realPath && $this->isCandidate($track, $realPath)) {
                $withCandidate[$directory] = true;
            }
        }

        return array_intersect_key($groups, $withCandidate);
    }

    private function collectOtherTrackFiles(array $directories): array
    {
        $prefix = $this->root.'/downloads/';
        $files = [];
        foreach ($this->tracks() as [, , $path]) {
            $realPath = realpath($path);
            if (false === $realPath || !str_starts_with($realPath, $prefix)) {
                continue;
            }

            $directory = $prefix.explode('/', substr($realPath, strlen($prefix)))[0];
            if (isset($directories[$directory])) {
                $files[$directory][$realPath] = true;
            }
        }

        return $files;
    }

    private function tracks(): \Generator
    {
        $cursor = $this->documentManager->createQueryBuilder(MultimediaObject::class)
            ->field('tracks')->exists(true)
            ->getQuery()
            ->execute()
        ;

        $processed = 0;
        foreach ($cursor as $multimediaObject) {
            foreach ($multimediaObject->getTracks() as $track) {
                $path = $this->path($track);
                if ('' !== $path) {
                    yield [$multimediaObject->getId(), $track, $path];
                }
            }

            if (0 === ++$processed % self::BATCH_SIZE) {
                $this->documentManager->clear();
            }
        }

        $this->documentManager->clear();
    }

    private function isCandidate(Media $track, string $realPath): bool
    {
        if (TrackDeliveryService::REASON_SHARED_HLS_DIRECTORY !== $this->trackDeliveryService->undeliverableReason($track)) {
            return false;
        }

        $prefix = $this->root.'/downloads/';
        if (!str_starts_with($realPath, $prefix)) {
            return false;
        }

        return 2 === count(explode('/', substr($realPath, strlen($prefix))));
    }

    private function moveTrack(array $collected, string $directory, array $otherFiles, bool $dryRun): array|string
    {
        $multimediaObject = $this->documentManager->find(MultimediaObject::class, $collected['mmId']);
        $track = $multimediaObject?->getTrackById($collected['trackId']);
        if (!$track instanceof Media || $this->path($track) !== $collected['path']) {
            return 'changed during the run';
        }

        $master = basename($collected['path']);
        $name = substr($master, 0, -strlen('.m3u8'));
        if (!str_ends_with($master, '.m3u8') || 1 !== preg_match(self::NAME_PATTERN, $name) || 1 !== preg_match(self::NAME_PATTERN, basename($directory))) {
            return 'invalid name';
        }

        if (is_link($collected['path']) || realpath($collected['path']) !== $directory.'/'.$master) {
            return 'symbolic link';
        }

        $destination = $directory.'/'.$name;
        if ((file_exists($destination) || is_link($destination)) && !$this->isEmptyDirectory($destination)) {
            return 'destination exists';
        }

        $files = $this->playlistFiles($directory, $master);
        if (is_string($files)) {
            return $files;
        }

        foreach ($files as $file) {
            if ($file !== $master && isset($otherFiles[$directory.'/'.$file])) {
                return 'file of another track: '.$file;
            }
        }

        $newPath = dirname($collected['path']).'/'.$name.'/'.$master;
        if ($dryRun) {
            return ['path' => $newPath];
        }

        $url = $track->storage()->url()?->url() ?? '';
        $suffix = '/'.$master;
        $newUrl = str_ends_with($url, $suffix) ? substr($url, 0, -strlen($suffix)).'/'.$name.$suffix : $url;

        $createdDirectories = [];
        $movedFiles = [];
        $error = $this->moveFiles($directory, $destination, $files, $createdDirectories, $movedFiles);
        if (null === $error) {
            try {
                $track->updateStorage(Storage::create(StorageUrl::create($newUrl), Path::create($newPath)));
                $this->documentManager->flush();
            } catch (\Throwable $exception) {
                $error = 'database update failed: '.$exception->getMessage();
            }
        }

        if (null === $error) {
            return ['path' => $newPath];
        }

        $notRestored = $this->rollback($directory, $destination, $createdDirectories, $movedFiles);
        if ([] !== $notRestored) {
            $error .= ', rollback incomplete: '.implode(' ', $notRestored);
        }

        return $error;
    }

    private function playlistFiles(string $directory, string $master): array|string
    {
        $files = [$master];
        $seen = [$master => true];
        $queue = [$master];
        while ([] !== $queue) {
            $playlist = array_shift($queue);
            $content = is_readable($directory.'/'.$playlist) ? file_get_contents($directory.'/'.$playlist) : false;
            if (false === $content) {
                return 'unreadable playlist: '.$playlist;
            }

            $base = dirname($playlist);
            foreach ($this->references($content) as $uri) {
                if (1 === preg_match('#^[A-Za-z][A-Za-z0-9+.-]*://#', $uri)) {
                    return 'absolute URL: '.$uri;
                }

                $relative = '.' === $base ? $uri : $base.'/'.$uri;
                $segments = explode('/', $relative);
                if (str_starts_with($uri, '/') || in_array('..', $segments, true)) {
                    return 'reference outside the directory: '.$uri;
                }
                if (in_array('', $segments, true) || in_array('.', $segments, true)) {
                    return 'invalid reference: '.$uri;
                }

                $realPath = realpath($directory.'/'.$relative);
                if (false === $realPath || !is_file($realPath)) {
                    return 'missing file: '.$relative;
                }
                if ($realPath !== $directory.'/'.$relative) {
                    return 'symbolic link: '.$relative;
                }

                if (isset($seen[$relative])) {
                    continue;
                }
                $seen[$relative] = true;
                $files[] = $relative;
                if ($this->isHls($relative)) {
                    $queue[] = $relative;
                }
            }
        }

        return $files;
    }

    private function references(string $content): array
    {
        $references = [];
        foreach (preg_split('/\R/', (string) preg_replace('/^\xEF\xBB\xBF/', '', $content)) ?: [] as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            if (!str_starts_with($line, '#')) {
                $references[] = $line;

                continue;
            }

            if (str_starts_with($line, '#EXT') && preg_match_all('/[:,]URI="([^"]*)"/', $line, $matches) > 0) {
                array_push($references, ...$matches[1]);
            }
        }

        return $references;
    }

    private function moveFiles(string $directory, string $destination, array $files, array &$createdDirectories, array &$movedFiles): ?string
    {
        foreach ($files as $file) {
            $target = $destination.'/'.$file;

            try {
                $this->createDirectory(dirname($target), $directory, $createdDirectories);
                $this->filesystem->rename($directory.'/'.$file, $target);
            } catch (IOException) {
                return 'move failed: '.$file;
            }

            $movedFiles[] = $file;
        }

        return null;
    }

    private function createDirectory(string $path, string $directory, array &$createdDirectories): void
    {
        if ($path === $directory || is_dir($path)) {
            return;
        }

        $this->createDirectory(dirname($path), $directory, $createdDirectories);
        $this->filesystem->mkdir($path);
        $createdDirectories[] = $path;
    }

    private function rollback(string $directory, string $destination, array $createdDirectories, array $movedFiles): array
    {
        $notRestored = [];
        foreach (array_reverse($movedFiles) as $file) {
            try {
                $this->filesystem->rename($destination.'/'.$file, $directory.'/'.$file);
            } catch (IOException) {
                $notRestored[] = $file;
            }
        }

        foreach (array_reverse($createdDirectories) as $createdDirectory) {
            if ($this->isEmptyDirectory($createdDirectory)) {
                rmdir($createdDirectory);
            }
        }

        return $notRestored;
    }

    private function isEmptyDirectory(string $path): bool
    {
        if (is_link($path) || !is_dir($path)) {
            return false;
        }

        $entries = scandir($path);

        return false !== $entries && [] === array_diff($entries, ['.', '..']);
    }

    private function isHls(string $path): bool
    {
        return str_ends_with(strtolower($path), '.m3u8');
    }

    private function path(Media $track): string
    {
        try {
            return $track->storage()->path()?->path() ?? '';
        } catch (PathException) {
            return (string) $track->getUrl();
        }
    }
}
