<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class TrackDownloadTest extends PumukitTestCase
{
    private const DOWNLOAD_SERVER = 'https://dls.example.org';

    private KernelBrowser $client;
    private MultimediaObject $multimediaObject;
    private string $storageDir;

    public function setUp(): void
    {
        $this->storageDir = (string) realpath(sys_get_temp_dir()).'/trackdownload-'.bin2hex(random_bytes(6));
        mkdir($this->storageDir.'/downloads/serie', 0777, true);
        mkdir($this->storageDir.'/masters/serie', 0777, true);
        file_put_contents($this->storageDir.'/downloads/serie/video.mp4', 'video');
        file_put_contents($this->storageDir.'/masters/serie/master.mp4', 'master');
        $_ENV['PUMUKIT_STORAGE_DIR'] = $_SERVER['PUMUKIT_STORAGE_DIR'] = $this->storageDir;
        $_ENV['PUMUKIT_DOWNLOAD_SERVER_URL'] = $_SERVER['PUMUKIT_DOWNLOAD_SERVER_URL'] = self::DOWNLOAD_SERVER;

        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->dm = $container->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $roles = array_values(array_filter(
            (new \ReflectionClass(Permission::class))->getConstants(),
            static fn ($value): bool => is_string($value) && str_starts_with($value, 'ROLE_')
        ));
        $user = new User();
        $user->setUsername('trackdownload');
        $user->setEmail('trackdownload@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN', ...$roles]);
        $this->dm->persist($user);
        $this->dm->flush();

        $factory = $container->get(FactoryService::class);
        $this->multimediaObject = $factory->createMultimediaObject($factory->createSeries(), true);
        $this->client->loginUser($user, 'pumukit');
    }

    public function tearDown(): void
    {
        unset($_ENV['PUMUKIT_DOWNLOAD_SERVER_URL'], $_SERVER['PUMUKIT_DOWNLOAD_SERVER_URL'], $_ENV['PUMUKIT_STORAGE_DIR'], $_SERVER['PUMUKIT_STORAGE_DIR']);
        $this->removeDirectory($this->storageDir);
        parent::tearDown();
    }

    public function testAMasterIsRedirectedToTheDownloadServerAsAttachment(): void
    {
        $track = $this->addTrack('masters/serie/master.mp4');

        $this->client->request('GET', $this->downloadUrl($track));

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        static::assertStringStartsWith(self::DOWNLOAD_SERVER.'/files/masters/serie/master.mp4?token=', $location);
        static::assertStringEndsWith('&forcedl=1', $location);
    }

    public function testADeliveryFileIsRedirectedToTheDownloadServerAsAttachment(): void
    {
        $track = $this->addTrack('downloads/serie/video.mp4');

        $this->client->request('GET', $this->downloadUrl($track));

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        static::assertStringStartsWith(self::DOWNLOAD_SERVER.'/files/downloads/serie/video.mp4?token=', $location);
        static::assertStringEndsWith('&forcedl=1', $location);
    }

    private function addTrack(string $relative): Track
    {
        $track = Track::create(
            'video.mp4',
            i18nText::create(['en' => '']),
            'en',
            Tags::create(['display']),
            false,
            true,
            0,
            Storage::create(StorageUrl::create(''), Path::create($this->storageDir.'/'.$relative)),
            VideoAudio::create('{"format":{"duration":"10.000000"}}')
        );
        $this->multimediaObject->addTrack($track);
        $this->dm->flush();

        return $track;
    }

    private function downloadUrl(Track $track): string
    {
        return static::getContainer()->get('router')->generate('pumukitnewadmin_track_download', [
            'mmId' => $this->multimediaObject->getId(),
            'id' => $track->id(),
        ]);
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
