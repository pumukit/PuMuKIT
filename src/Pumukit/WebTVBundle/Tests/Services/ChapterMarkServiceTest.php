<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Tests\Services;

use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Annotation;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\WebTVBundle\Services\ChapterMarkService;

/**
 * @internal
 */
#[CoversNothing]
class ChapterMarkServiceTest extends PumukitTestCase
{
    private ChapterMarkService $service;
    private MultimediaObject $multimediaObject;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(ChapterMarkService::class);

        $series = new Series();
        $series->setNumericalID(1);
        $this->dm->persist($series);
        $this->multimediaObject = new MultimediaObject();
        $this->multimediaObject->setNumericalID(1);
        $this->multimediaObject->setSeries($series);
        $this->dm->persist($this->multimediaObject);
        $this->dm->flush();
    }

    public function testWithoutMarksThereAreNoChapters(): void
    {
        $this->annotation('paella/trimming', ['trimming' => ['start' => 10, 'end' => 90]]);

        $this->assertSame([], $this->service->getChapterMarks($this->multimediaObject));
    }

    public function testWithoutTrimmingEveryMarkIsAChapter(): void
    {
        $this->annotation('paella/marks', ['marks' => [['s' => 60, 'name' => 'Second'], ['s' => 5, 'name' => 'First']]]);

        $this->assertSame([
            ['title' => 'First', 'real_time' => 5, 'time_to_show' => 5],
            ['title' => 'Second', 'real_time' => 60, 'time_to_show' => 60],
        ], $this->service->getChapterMarks($this->multimediaObject));
    }

    public function testAnEmptyTrimmingKeepsEveryMark(): void
    {
        $this->annotation('paella/marks', ['marks' => [['s' => 5, 'name' => 'First']]]);
        $this->annotation('paella/trimming', ['trimming' => null]);

        $this->assertSame([
            ['title' => 'First', 'real_time' => 5, 'time_to_show' => 5],
        ], $this->service->getChapterMarks($this->multimediaObject));
    }

    public function testWithTrimmingOnlyTheMarksInsideAreShownFromTheNewStart(): void
    {
        $this->annotation('paella/marks', ['marks' => [['s' => 5, 'name' => 'Cut'], ['s' => 40, 'name' => 'Kept'], ['s' => 95, 'name' => 'Cut too']]]);
        $this->annotation('paella/trimming', ['trimming' => ['start' => 10, 'end' => 90]]);

        $this->assertSame([
            ['title' => 'Kept', 'real_time' => 40, 'time_to_show' => 30],
        ], $this->service->getChapterMarks($this->multimediaObject));
    }

    private function annotation(string $type, array $value): void
    {
        $annotation = new Annotation();
        $annotation->setMultimediaObject(new ObjectId($this->multimediaObject->getId()));
        $annotation->setType($type);
        $annotation->setValue(json_encode($value, JSON_THROW_ON_ERROR));
        $annotation->setInPoint(0);
        $annotation->setOutPoint(0);
        $annotation->setLength(0);
        $annotation->setPrivate(false);
        $annotation->setCreated(new \DateTime());
        $annotation->setUserId('test');
        $annotation->setSession('test');
        $this->dm->persist($annotation);
        $this->dm->flush();
    }
}
