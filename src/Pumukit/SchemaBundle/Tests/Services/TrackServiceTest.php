<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\Document;
use Pumukit\SchemaBundle\Document\MediaType\Image;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\Exif;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\Generic;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\MediaMetadata;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TrackService;

/**
 * @internal
 *
 * @coversNothing
 */
class TrackServiceTest extends PumukitTestCase
{
    private const VIDEO = '{"format":{"duration":"10"},"streams":[{"codec_type":"video","width":640,"height":360},{"codec_type":"audio"}]}';
    private const AUDIO = '{"format":{"duration":"10"},"streams":[{"codec_type":"audio"}]}';

    private TrackService $trackService;
    private FactoryService $factoryService;

    public function setUp(): void
    {
        parent::setUp();
        $this->trackService = self::getContainer()->get(TrackService::class);
        $this->factoryService = self::getContainer()->get(FactoryService::class);
    }

    public function testTheFirstMediaGivesTheTypeToAnObjectWithoutType(): void
    {
        $media = [
            MultimediaObject::TYPE_VIDEO => Track::create(...$this->media(VideoAudio::create(self::VIDEO))),
            MultimediaObject::TYPE_AUDIO => Track::create(...$this->media(VideoAudio::create(self::AUDIO))),
            MultimediaObject::TYPE_IMAGE => Image::create(...$this->media(Exif::create('{}'))),
            MultimediaObject::TYPE_DOCUMENT => Document::create(...$this->media(Generic::create('{}'))),
        ];

        foreach ($media as $type => $medium) {
            $multimediaObject = $this->multimediaObject();

            $this->trackService->addTrackToMultimediaObject($multimediaObject, $medium);

            $this->assertSame($type, $multimediaObject->getType());
        }
    }

    public function testTheTypeOfAnObjectIsKept(): void
    {
        $multimediaObject = $this->multimediaObject();
        $multimediaObject->setAudioType();

        $this->trackService->addTrackToMultimediaObject($multimediaObject, Track::create(...$this->media(VideoAudio::create(self::VIDEO))));

        $this->assertSame(MultimediaObject::TYPE_AUDIO, $multimediaObject->getType());
    }

    private function multimediaObject(): MultimediaObject
    {
        $multimediaObject = $this->factoryService->createMultimediaObject($this->factoryService->createSeries());
        $this->assertSame(MultimediaObject::TYPE_UNKNOWN, $multimediaObject->getType());

        return $multimediaObject;
    }

    private function media(MediaMetadata $metadata): array
    {
        return [
            'media.mp4',
            i18nText::create(['en' => '']),
            'en',
            Tags::create(['display']),
            false,
            false,
            0,
            Storage::external(StorageUrl::create('https://media.example.org/media.mp4')),
            $metadata,
        ];
    }
}
