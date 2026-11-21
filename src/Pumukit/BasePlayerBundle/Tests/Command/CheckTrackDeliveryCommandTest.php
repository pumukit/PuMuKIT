<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\Document;
use Pumukit\SchemaBundle\Document\MediaType\Image;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\Generic;
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
class CheckTrackDeliveryCommandTest extends PumukitTestCase
{
    private string $storageDir;
    private array $previousEnv = [];
    private int $numericalId = 0;

    public function setUp(): void
    {
        $this->storageDir = (string) realpath(sys_get_temp_dir()).'/trackdelivery-'.bin2hex(random_bytes(6));
        mkdir($this->storageDir.'/downloads/serie', 0777, true);
        mkdir($this->storageDir.'/masters', 0777, true);
        mkdir($this->storageDir.'/inbox', 0777, true);
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

    public function testAllDeliverableExitsZero(): void
    {
        $this->addTrack('downloads/serie/ok.mp4');

        $tester = $this->commandTester();
        $tester->execute([]);

        static::assertSame(Command::SUCCESS, $tester->getStatusCode());
        static::assertStringContainsString('Checked 1 tracks, 0 cannot be served', $tester->getDisplay());
    }

    public function testListsTracksOutsideTheStorage(): void
    {
        $outside = $this->addTrack('inbox/outside.mp4');
        $missing = $this->addTrack('downloads/serie/gone.mp4', false);
        $this->addTrack('downloads/serie/ok.mp4');

        $tester = $this->commandTester();
        $tester->execute([]);

        $display = $tester->getDisplay();
        static::assertSame(Command::FAILURE, $tester->getStatusCode());
        static::assertStringContainsString('Checked 3 tracks, 2 cannot be served', $display);
        static::assertStringContainsString($outside[0].' '.$outside[1].' '.$this->storageDir.'/inbox/outside.mp4 outside the storage', $display);
        static::assertStringContainsString($missing[0].' '.$missing[1].' '.$this->storageDir.'/downloads/serie/gone.mp4 missing file', $display);
    }

    public function testLimitCapsTheList(): void
    {
        $this->addTrack('inbox/a.mp4');
        $this->addTrack('inbox/b.mp4');
        $this->addTrack('inbox/c.mp4');

        $tester = $this->commandTester();
        $tester->execute(['--limit' => '1']);

        $display = $tester->getDisplay();
        static::assertSame(Command::FAILURE, $tester->getStatusCode());
        static::assertStringContainsString('Checked 3 tracks, 3 cannot be served', $display);
        static::assertSame(1, substr_count($display, 'outside the storage'));
    }

    public function testAnInvalidLimitIsRejected(): void
    {
        $this->addTrack('inbox/a.mp4');

        foreach (['abc', '-5', '1.5', ''] as $value) {
            $tester = $this->commandTester();
            $tester->execute(['--limit' => $value]);

            static::assertSame(Command::INVALID, $tester->getStatusCode(), $value);
            static::assertStringContainsString('non-negative integer', $tester->getDisplay());
            static::assertStringNotContainsString('Checked', $tester->getDisplay());
        }
    }

    public function testExternalTracksAreNeitherCountedNorListed(): void
    {
        $this->addTrack('downloads/serie/ok.mp4');
        $track = Track::create(
            'external.mp4',
            i18nText::create(['en' => '']),
            'en',
            Tags::create(['display']),
            false,
            true,
            0,
            Storage::create(StorageUrl::create('https://example.org/external.mp4'), null),
            VideoAudio::create('{"format":{"duration":"10.000000"}}')
        );
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(++$this->numericalId);
        $multimediaObject->addTrack($track);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $tester = $this->commandTester();
        $tester->execute([]);

        static::assertSame(Command::SUCCESS, $tester->getStatusCode());
        static::assertStringContainsString('Checked 1 tracks, 0 cannot be served', $tester->getDisplay());
        static::assertStringNotContainsString('external.mp4', $tester->getDisplay());
    }

    public function testListsDocumentsAndImagesOfObjectsWithoutTracks(): void
    {
        $documentPath = $this->storageDir.'/inbox/document.pdf';
        $imagePath = $this->storageDir.'/downloads/serie/gone.jpg';
        file_put_contents($documentPath, 'pdf');
        file_put_contents($imagePath, 'jpg');
        $storage = static fn (string $path): Storage => Storage::create(StorageUrl::create(''), Path::create($path));
        $document = Document::create('document.pdf', i18nText::create(['en' => '']), 'en', Tags::create([]), false, true, 0, $storage($documentPath), Generic::create(''));
        $image = Image::create('gone.jpg', i18nText::create(['en' => '']), 'en', Tags::create([]), false, true, 0, $storage($imagePath), Generic::create(''));
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(++$this->numericalId);
        $multimediaObject->addDocument($document);
        $multimediaObject->addImage($image);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();
        unlink($imagePath);

        $tester = $this->commandTester();
        $tester->execute([]);

        $display = $tester->getDisplay();
        static::assertSame(Command::FAILURE, $tester->getStatusCode());
        static::assertStringContainsString('Checked 2 tracks, 2 cannot be served', $display);
        static::assertStringContainsString($multimediaObject->getId().' '.$document->id().' '.$documentPath.' outside the storage', $display);
        static::assertStringContainsString($multimediaObject->getId().' '.$image->id().' '.$imagePath.' missing file', $display);
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);

        return new CommandTester($application->find('pumukit:check:track-delivery'));
    }

    private function addTrack(string $relative, bool $create = true): array
    {
        $path = $this->storageDir.'/'.$relative;
        file_put_contents($path, 'data');

        $track = Track::create(
            'pumukit.mp4',
            i18nText::create(['en' => '']),
            'en',
            Tags::create(['display']),
            false,
            true,
            0,
            Storage::create(StorageUrl::create(''), Path::create($path)),
            VideoAudio::create('{"format":{"duration":"10.000000"}}')
        );
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(++$this->numericalId);
        $multimediaObject->addTrack($track);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();
        if (!$create) {
            unlink($path);
        }

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
