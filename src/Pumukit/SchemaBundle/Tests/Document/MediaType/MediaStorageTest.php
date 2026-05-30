<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document\MediaType;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;

/**
 * @internal
 *
 * @coversNothing
 */
class MediaStorageTest extends PumukitTestCase
{
    private string $tmpFile;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->tmpFile = sys_get_temp_dir().'/pumukit-media-storage-'.uniqid('', true).'.mp4';
        touch($this->tmpFile);
    }

    public function tearDown(): void
    {
        if (isset($this->tmpFile) && file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        parent::tearDown();
    }

    public function testLocalStorageExposesPathAndUrl(): void
    {
        $storage = Storage::create(
            StorageUrl::create('http://example.com/video.mp4'),
            Path::create($this->tmpFile),
        );

        $raw = $storage->toArray();

        $this->assertSame('http://example.com/video.mp4', $raw['url']);
        $this->assertSame($this->tmpFile, $raw['path']);
    }

    public function testExternalStorageHasNullPath(): void
    {
        $storage = Storage::external(StorageUrl::create('https://youtu.be/abc'));

        $raw = $storage->toArray();

        $this->assertSame('https://youtu.be/abc', $raw['url']);
        $this->assertNull($raw['path']);
    }

    public function testTrackPersistsStoragePathInRawDocument(): void
    {
        $track = $this->buildTrack($this->tmpFile);

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setTitle('media-storage-test');
        $multimediaObject->addTrack($track);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();
        $this->dm->clear();

        $raw = $this->dm->createQueryBuilder(MultimediaObject::class)
            ->field('_id')->equals($multimediaObject->getId())
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult()
        ;

        $this->assertIsArray($raw);
        $this->assertSame($this->tmpFile, $raw['tracks'][0]['storage']['path']);
    }

    private function buildTrack(string $path): Track
    {
        return Track::create(
            'video.mp4',
            i18nText::create(['en' => '']),
            'en',
            Tags::create(['master']),
            false,
            true,
            0,
            Storage::create(StorageUrl::create(''), Path::create($path)),
            VideoAudio::create('{"format":{"duration":"10.000000"}}'),
        );
    }
}
