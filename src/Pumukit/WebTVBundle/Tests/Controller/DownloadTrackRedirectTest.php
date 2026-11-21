<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class DownloadTrackRedirectTest extends PumukitTestCase
{
    private const DOWNLOAD_SERVER = 'https://dls.example.org';

    private KernelBrowser $client;
    private string $storageDir;
    private string $trackPath;
    private array $previousEnv = [];
    private Tag $webTVTag;
    private Series $series;

    public function setUp(): void
    {
        $this->storageDir = (string) realpath(sys_get_temp_dir()).'/webtv-'.bin2hex(random_bytes(6));
        mkdir($this->storageDir.'/downloads/media', 0777, true);
        foreach (['PUMUKIT_STORAGE_DIR' => $this->storageDir, 'PUMUKIT_DOWNLOAD_SERVER_URL' => self::DOWNLOAD_SERVER] as $name => $value) {
            $this->previousEnv[$name] = [$_ENV[$name] ?? null, $_SERVER[$name] ?? null];
            $_ENV[$name] = $_SERVER[$name] = $value;
        }
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();
        $this->trackPath = $this->storageDir.'/downloads/media/pumukit.mp4';
        copy(static::getContainer()->getParameter('kernel.project_dir').'/tests/files/pumukit.mp4', $this->trackPath);

        $this->webTVTag = new Tag();
        $this->webTVTag->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $this->dm->persist($this->webTVTag);
        $this->series = new Series();
        $this->series->setNumericalID(1);
        $this->dm->persist($this->series);
        $this->dm->flush();
    }

    public function tearDown(): void
    {
        foreach ($this->previousEnv as $name => [$env, $server]) {
            null === $env ? $this->unsetKey($_ENV, $name) : $_ENV[$name] = $env;
            null === $server ? $this->unsetKey($_SERVER, $name) : $_SERVER[$name] = $server;
        }
        $this->removeDirectory($this->storageDir);
        parent::tearDown();
    }

    public function testTheVideoPageSendsTheDownloadTrackToTheSignedTrackfileUrl(): void
    {
        [$multimediaObject, $track] = $this->createMultimediaObjectWithTrack(MultimediaObject::STATUS_PUBLISHED, '/storage/downloads/pumukit.mp4');

        $this->client->request('GET', '/video/'.$multimediaObject->getId().'?track_id='.$track->id());

        $this->assertTrackfileRedirect($track, true);
    }

    public function testTheMagicVideoPageSendsTheDownloadTrackToTheSignedTrackfileUrl(): void
    {
        [$multimediaObject, $track] = $this->createMultimediaObjectWithTrack(MultimediaObject::STATUS_HIDDEN, '/storage/downloads/pumukit.mp4');

        $this->client->request('GET', '/video/magic/'.$multimediaObject->getSecret().'?track_id='.$track->id());

        $this->assertTrackfileRedirect($track, true);
    }

    public function testThePlayerSendsTheDownloadTrackToTheSignedTrackfileUrl(): void
    {
        [$multimediaObject, $track] = $this->createMultimediaObjectWithTrack(MultimediaObject::STATUS_PUBLISHED, '/storage/downloads/pumukit.mp4');

        $this->client->request('GET', '/player/'.$multimediaObject->getId().'?track_id='.$track->id());

        $this->assertTrackfileRedirect($track, false);
    }

    public function testTheDownloadOfALocalTrackEndsOnTheDownloadServerAsAnAttachment(): void
    {
        [$multimediaObject, $track] = $this->createMultimediaObjectWithTrack(MultimediaObject::STATUS_PUBLISHED, '/storage/downloads/pumukit.mp4');

        $this->assertDownloadEndsOnTheDownloadServer($multimediaObject, $track);
    }

    public function testTheDownloadOfATrackOnTheDownloadServerEndsThereAsAnAttachment(): void
    {
        [$multimediaObject, $track] = $this->createMultimediaObjectWithTrack(MultimediaObject::STATUS_PUBLISHED, self::DOWNLOAD_SERVER.'/pumukit/pumukit.mp4');

        $this->assertDownloadEndsOnTheDownloadServer($multimediaObject, $track);
    }

    private function assertDownloadEndsOnTheDownloadServer(MultimediaObject $multimediaObject, Track $track): void
    {
        $this->client->request('GET', '/video/'.$multimediaObject->getId().'?track_id='.$track->id());
        $this->client->request('GET', $this->client->getResponse()->headers->get('Location'));

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringStartsWith(self::DOWNLOAD_SERVER.'/files/downloads/media/pumukit.mp4?', $location);
        $this->assertStringContainsString('token=', $location);
        $this->assertStringEndsWith('&forcedl=1', $location);
    }

    private function assertTrackfileRedirect(Track $track, bool $forceDownload): void
    {
        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringStartsWith('http://localhost/trackfile/'.$track->id().'.mp4?', $location);
        $this->assertStringContainsString('token=', $location);
        $this->assertStringNotContainsString($this->storageDir, $location);
        if ($forceDownload) {
            $this->assertStringContainsString('forcedl=1', $location);
        } else {
            $this->assertStringNotContainsString('forcedl', $location);
        }
    }

    private function createMultimediaObjectWithTrack(int $status, string $url): array
    {
        $track = $this->createTrack('download', $url);
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setStatus($status);
        $multimediaObject->addTag($this->webTVTag);
        $multimediaObject->setSeries($this->series);
        $multimediaObject->addTrack($this->createTrack('display', $url));
        $multimediaObject->addTrack($track);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return [$multimediaObject, $track];
    }

    private function createTrack(string $tag, string $url): Track
    {
        return Track::create(
            'pumukit.mp4',
            i18nText::create(['en' => '']),
            'en',
            Tags::create([$tag]),
            false,
            true,
            0,
            Storage::create(StorageUrl::create($url), Path::create($this->trackPath)),
            VideoAudio::create('{"format":{"duration":"10.000000"}}')
        );
    }

    private function unsetKey(array &$bag, string $name): void
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
