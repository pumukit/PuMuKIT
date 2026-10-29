<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobRender;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

/**
 * @internal
 */
#[CoversNothing]
class JobRenderTest extends PumukitTestCase
{
    public function testThePathsAreEscapedForTheDoubleQuotesOfTheShellCommand(): void
    {
        $series = new Series();
        $series->setNumericalID(1);
        $this->dm->persist($series);

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setSeries($series);
        $this->dm->persist($multimediaObject);

        $job = new Job();
        $job->setMmId($multimediaObject->getId());
        $job->setProfile('master_copy');
        $job->setPathIni('/tmp/Tom & Jerry "1" $(id) `id` \\.mp4');
        $job->setPathEnd('/tmp/output.mp4');
        $this->dm->persist($job);
        $this->dm->flush();

        $command = static::getContainer()->get(JobRender::class)->renderBat($job);

        $this->assertSame('cp "/tmp/Tom & Jerry \\"1\\" \\$(id) \\`id\\` \\\\.mp4" "/tmp/output.mp4"', $command);
    }
}
