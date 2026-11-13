<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Pumukit\PaellaPlayerBundle\Services\FrameListManifest;
use Pumukit\SchemaBundle\Document\EmbeddedSegment;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @internal
 */
#[CoversNothing]
class FrameListManifestTest extends TestCase
{
    public function testTheFramesAreTheSegmentsOfTheMultimediaObject(): void
    {
        $segment = new EmbeddedSegment();
        $segment->setTime(65400);
        $segment->setPreview('https://oc.example.org/static/65.jpg');
        $segment->setText('Slide 2');

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setEmbeddedSegments([$segment]);

        $this->assertSame([[
            'id' => 'frame_65',
            'mimetype' => 'image/jpeg',
            'time' => 65,
            'url' => 'https://oc.example.org/static/65.jpg',
            'thumb' => 'https://oc.example.org/static/65.jpg',
            'caption' => 'Slide 2',
        ]], (new FrameListManifest())->create($multimediaObject));
    }

    public function testAMultimediaObjectWithoutSegmentsHasNoFrames(): void
    {
        $this->assertSame([], (new FrameListManifest())->create(new MultimediaObject()));
    }
}
