<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\BasePlayerBundle\Services\TrackDeliveryService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversNothing]
class MoveHlsToOwnDirectoryCommandTest extends PumukitTestCase
{
    private const URL = 'https://tv.example.org/storage/downloads/s/';

    private string $storageDir;
    private string $dir;
    private array $previousEnv = [];
    private int $numericalId = 0;

    public function setUp(): void
    {
        $this->storageDir = (string) realpath(sys_get_temp_dir()).'/movehls-'.bin2hex(random_bytes(6));
        $this->dir = $this->storageDir.'/downloads/s';
        mkdir($this->dir, 0777, true);
        foreach (['PUMUKIT_STORAGE_DIR' => $this->storageDir, 'PUMUKIT_DOWNLOAD_SERVER_URL' => 'https://dls.example.org'] as $name => $value) {
            $this->previousEnv[$name] = [$_ENV[$name] ?? null, $_SERVER[$name] ?? null];
            $_ENV[$name] = $_SERVER[$name] = $value;
        }
        self::ensureKernelShutdown();
        static::bootKernel(['environment' => 'test']);
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();
    }

    public function tearDown(): void
    {
        foreach ($this->previousEnv as $name => [$env, $server]) {
            null === $env ? $this->unsetVariable($_ENV, $name) : $_ENV[$name] = $env;
            null === $server ? $this->unsetVariable($_SERVER, $name) : $_SERVER[$name] = $server;
        }
        $this->removeDirectory($this->storageDir);
        parent::tearDown();
    }

    public function testMovesASingleQualityHls(): void
    {
        $this->writeSingleQuality('x');
        file_put_contents($this->dir.'/y.mp4', 'mp4');
        [$mmId, $trackId] = $this->addTrack('x.m3u8');
        $this->addTrack('y.mp4');

        $tester = $this->runCommand();

        static::assertSame(Command::SUCCESS, $tester->getStatusCode());
        static::assertStringContainsString(sprintf('%s %s %s/x.m3u8 -> %s/x/x.m3u8', $mmId, $trackId, $this->dir, $this->dir), $tester->getDisplay());
        foreach (['x.m3u8', 'index.m3u8', 'data_000.ts'] as $file) {
            static::assertFileExists($this->dir.'/x/'.$file);
            static::assertFileDoesNotExist($this->dir.'/'.$file);
        }
        static::assertFileExists($this->dir.'/y.mp4');
        static::assertFileDoesNotExist($this->dir.'/x/y.mp4');
        $this->assertTrackMoved($mmId, $trackId, 'x');
    }

    public function testMovesSeveralQualitiesInTheSameDirectory(): void
    {
        file_put_contents($this->dir.'/x.m3u8', "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=4000000\nindex_high.m3u8\n#EXT-X-STREAM-INF:BANDWIDTH=1000000\nindex_low.m3u8\n");
        file_put_contents($this->dir.'/index_high.m3u8', "#EXTM3U\n#EXTINF:10,\ndata_high_000.ts\n#EXTINF:10,\ndata_high_001.ts\n#EXT-X-ENDLIST\n");
        file_put_contents($this->dir.'/index_low.m3u8', "#EXTM3U\n#EXTINF:10,\ndata_low_000.ts\n#EXT-X-ENDLIST\n");
        foreach (['data_high_000.ts', 'data_high_001.ts', 'data_low_000.ts'] as $segment) {
            file_put_contents($this->dir.'/'.$segment, 'ts');
        }
        [$mmId, $trackId] = $this->addTrack('x.m3u8');

        $tester = $this->runCommand();

        static::assertSame(Command::SUCCESS, $tester->getStatusCode());
        foreach (['x.m3u8', 'index_high.m3u8', 'index_low.m3u8', 'data_high_000.ts', 'data_high_001.ts', 'data_low_000.ts'] as $file) {
            static::assertFileExists($this->dir.'/x/'.$file);
            static::assertFileDoesNotExist($this->dir.'/'.$file);
        }
        $this->assertTrackMoved($mmId, $trackId, 'x');
    }

    public function testMovesSubdirectoryQualities(): void
    {
        mkdir($this->dir.'/stream_0');
        mkdir($this->dir.'/stream_1');
        file_put_contents($this->dir.'/x.m3u8', "#EXTM3U\n#EXT-X-MEDIA:TYPE=AUDIO,GROUP-ID=\"a\",NAME=\"a\",URI=\"stream_1/audio.m3u8\"\n#EXT-X-STREAM-INF:BANDWIDTH=4000000,AUDIO=\"a\"\nstream_0/stream.m3u8\n");
        file_put_contents($this->dir.'/stream_0/stream.m3u8', "#EXTM3U\n#EXTINF:10,\ndata00.ts\n#EXT-X-ENDLIST\n");
        file_put_contents($this->dir.'/stream_0/data00.ts', 'ts');
        file_put_contents($this->dir.'/stream_1/audio.m3u8', "#EXTM3U\n#EXT-X-MAP:URI=\"init.mp4\"\n#EXTINF:10,\naudio00.m4s\n#EXT-X-ENDLIST\n");
        file_put_contents($this->dir.'/stream_1/init.mp4', 'init');
        file_put_contents($this->dir.'/stream_1/audio00.m4s', 'm4s');
        [$mmId, $trackId] = $this->addTrack('x.m3u8');

        $tester = $this->runCommand();

        static::assertSame(Command::SUCCESS, $tester->getStatusCode());
        foreach (['x.m3u8', 'stream_0/stream.m3u8', 'stream_0/data00.ts', 'stream_1/audio.m3u8', 'stream_1/init.mp4', 'stream_1/audio00.m4s'] as $file) {
            static::assertFileExists($this->dir.'/x/'.$file);
            static::assertFileDoesNotExist($this->dir.'/'.$file);
        }
        $this->assertTrackMoved($mmId, $trackId, 'x');
    }

    public function testSharedDirectoryIsListedNotMoved(): void
    {
        $this->writeSingleQuality('x');
        file_put_contents($this->dir.'/z.m3u8', "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=4000000\nindex.m3u8\n");
        [$xMmId, $xTrackId] = $this->addTrack('x.m3u8');
        [$zMmId, $zTrackId] = $this->addTrack('z.m3u8');

        $tester = $this->runCommand();

        $display = $tester->getDisplay();
        static::assertSame(Command::FAILURE, $tester->getStatusCode());
        static::assertStringContainsString(sprintf('%s %s %s/x.m3u8 shared, re-encode', $xMmId, $xTrackId, $this->dir), $display);
        static::assertStringContainsString(sprintf('%s %s %s/z.m3u8 shared, re-encode', $zMmId, $zTrackId, $this->dir), $display);
        $this->assertNothingMoved(['x.m3u8', 'z.m3u8', 'index.m3u8', 'data_000.ts'], 'x', $xMmId, $xTrackId);
        static::assertDirectoryDoesNotExist($this->dir.'/z');
    }

    public function testDryRunChangesNothing(): void
    {
        $this->writeSingleQuality('x');
        [$mmId, $trackId] = $this->addTrack('x.m3u8');

        $tester = $this->runCommand(['--dry-run' => true]);

        static::assertSame(Command::SUCCESS, $tester->getStatusCode());
        static::assertStringContainsString(sprintf('%s %s %s/x.m3u8 -> %s/x/x.m3u8', $mmId, $trackId, $this->dir, $this->dir), $tester->getDisplay());
        $this->assertNothingMoved(['x.m3u8', 'index.m3u8', 'data_000.ts'], 'x', $mmId, $trackId);
    }

    public function testSecondRunFindsNothing(): void
    {
        $this->writeSingleQuality('x');
        [$mmId, $trackId] = $this->addTrack('x.m3u8');

        $this->runCommand();
        $tester = $this->runCommand();

        static::assertSame(Command::SUCCESS, $tester->getStatusCode());
        static::assertStringNotContainsString($trackId, $tester->getDisplay());
        static::assertStringContainsString('Moved 0 tracks', $tester->getDisplay());
        $this->assertTrackMoved($mmId, $trackId, 'x');
    }

    public function testReferenceOutsideTheDirectoryIsNotMoved(): void
    {
        mkdir($this->storageDir.'/downloads/other');
        file_put_contents($this->storageDir.'/downloads/other/x.ts', 'ts');
        file_put_contents($this->dir.'/x.m3u8', "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=4000000\nindex.m3u8\n");
        file_put_contents($this->dir.'/index.m3u8', "#EXTM3U\n#EXTINF:10,\ndata_000.ts\n#EXTINF:10,\n../other/x.ts\n#EXT-X-ENDLIST\n");
        file_put_contents($this->dir.'/data_000.ts', 'ts');
        [$mmId, $trackId] = $this->addTrack('x.m3u8');

        $tester = $this->runCommand();

        static::assertSame(Command::FAILURE, $tester->getStatusCode());
        static::assertStringContainsString(sprintf('%s %s %s/x.m3u8 reference outside the directory: ../other/x.ts', $mmId, $trackId, $this->dir), $tester->getDisplay());
        $this->assertNothingMoved(['x.m3u8', 'index.m3u8', 'data_000.ts'], 'x', $mmId, $trackId);
        static::assertFileExists($this->storageDir.'/downloads/other/x.ts');
    }

    public function testMissingSegmentIsNotMoved(): void
    {
        file_put_contents($this->dir.'/x.m3u8', "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=4000000\nindex.m3u8\n");
        file_put_contents($this->dir.'/index.m3u8', "#EXTM3U\n#EXTINF:10,\ndata_000.ts\n#EXTINF:10,\ndata_001.ts\n#EXT-X-ENDLIST\n");
        file_put_contents($this->dir.'/data_000.ts', 'ts');
        [$mmId, $trackId] = $this->addTrack('x.m3u8');

        $tester = $this->runCommand();

        static::assertSame(Command::FAILURE, $tester->getStatusCode());
        static::assertStringContainsString(sprintf('%s %s %s/x.m3u8 missing file: data_001.ts', $mmId, $trackId, $this->dir), $tester->getDisplay());
        $this->assertNothingMoved(['x.m3u8', 'index.m3u8', 'data_000.ts'], 'x', $mmId, $trackId);
    }

    public function testAbsoluteUrlIsNotMoved(): void
    {
        file_put_contents($this->dir.'/x.m3u8', "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=4000000\nindex.m3u8\n");
        file_put_contents($this->dir.'/index.m3u8', "#EXTM3U\n#EXT-X-KEY:METHOD=AES-128,URI=\"https://keys.example.org/k\"\n#EXTINF:10,\ndata_000.ts\n#EXT-X-ENDLIST\n");
        file_put_contents($this->dir.'/data_000.ts', 'ts');
        [$mmId, $trackId] = $this->addTrack('x.m3u8');

        $tester = $this->runCommand();

        static::assertSame(Command::FAILURE, $tester->getStatusCode());
        static::assertStringContainsString(sprintf('%s %s %s/x.m3u8 absolute URL: https://keys.example.org/k', $mmId, $trackId, $this->dir), $tester->getDisplay());
        $this->assertNothingMoved(['x.m3u8', 'index.m3u8', 'data_000.ts'], 'x', $mmId, $trackId);
    }

    public function testExistingDestinationIsNotMoved(): void
    {
        $this->writeSingleQuality('x');
        mkdir($this->dir.'/x');
        file_put_contents($this->dir.'/x/other.ts', 'ts');
        [$mmId, $trackId] = $this->addTrack('x.m3u8');

        $tester = $this->runCommand();

        static::assertSame(Command::FAILURE, $tester->getStatusCode());
        static::assertStringContainsString(sprintf('%s %s %s/x.m3u8 destination exists', $mmId, $trackId, $this->dir), $tester->getDisplay());
        foreach (['x.m3u8', 'index.m3u8', 'data_000.ts'] as $file) {
            static::assertFileExists($this->dir.'/'.$file);
        }
        static::assertFileDoesNotExist($this->dir.'/x/x.m3u8');
    }

    private function writeSingleQuality(string $name): void
    {
        file_put_contents($this->dir.'/'.$name.'.m3u8', "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-STREAM-INF:BANDWIDTH=4000000\nindex.m3u8\n");
        file_put_contents($this->dir.'/index.m3u8', "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-TARGETDURATION:10\n#EXTINF:10,\ndata_000.ts\n#EXT-X-ENDLIST\n");
        file_put_contents($this->dir.'/data_000.ts', 'ts');
    }

    private function assertTrackMoved(string $mmId, string $trackId, string $name): void
    {
        $track = $this->findTrack($mmId, $trackId);
        static::assertSame($this->dir.'/'.$name.'/'.$name.'.m3u8', $track->storage()->path()->path());
        static::assertSame(self::URL.$name.'/'.$name.'.m3u8', $track->storage()->url()->url());
        static::assertNull(static::getContainer()->get(TrackDeliveryService::class)->undeliverableReason($track));
    }

    private function assertNothingMoved(array $files, string $name, string $mmId, string $trackId): void
    {
        foreach ($files as $file) {
            static::assertFileExists($this->dir.'/'.$file);
        }
        static::assertDirectoryDoesNotExist($this->dir.'/'.$name);
        $track = $this->findTrack($mmId, $trackId);
        static::assertSame($this->dir.'/'.$name.'.m3u8', $track->storage()->path()->path());
        static::assertSame(self::URL.$name.'.m3u8', $track->storage()->url()->url());
    }

    private function findTrack(string $mmId, string $trackId): Track
    {
        $this->dm->clear();
        $multimediaObject = $this->dm->find(MultimediaObject::class, $mmId);

        return $multimediaObject->getTrackById($trackId);
    }

    private function runCommand(array $input = []): CommandTester
    {
        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('pumukit:hls:move-to-own-directory'));
        $tester->execute($input);

        return $tester;
    }

    private function addTrack(string $file): array
    {
        $track = Track::create(
            'pumukit.'.pathinfo($file, PATHINFO_EXTENSION),
            i18nText::create(['en' => '']),
            'en',
            Tags::create(['display']),
            false,
            true,
            0,
            Storage::create(StorageUrl::create(self::URL.$file), Path::create($this->dir.'/'.$file)),
            VideoAudio::create('{"format":{"duration":"10.000000"}}')
        );
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(++$this->numericalId);
        $multimediaObject->addTrack($track);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return [$multimediaObject->getId(), $track->id()];
    }

    private function unsetVariable(array &$bag, string $name): void
    {
        unset($bag[$name]);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
