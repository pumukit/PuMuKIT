<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\ProfileValidator;

/**
 * @internal
 */
#[CoversNothing]
class ProfileValidatorTest extends TestCase
{
    #[DataProvider('bestProfiles')]
    public function testSearchBestProfileForFile(string $file, string $genericProfile, string $expected): void
    {
        $validator = new ProfileValidator(new ProfileService([]), new NullLogger());

        $this->assertSame($expected, $validator->searchBestProfileForFile($genericProfile, dirname(__DIR__, 5).'/tests/files/'.$file));
    }

    public static function bestProfiles(): iterable
    {
        yield 'mp4 broadcastable' => ['pumukit.mp4', 'broadcastable', 'video_master_broadcastable'];

        yield 'mxf broadcastable' => ['pumukit.mxf', 'broadcastable', 'video_master_broadcastable'];

        yield 'mxf encoded' => ['pumukit.mxf', 'master_encoded', 'video_master_encoded'];

        yield 'pdf' => ['pumukit.pdf', 'broadcastable', 'master_copy'];

        yield 'mp3' => ['pumukit.mp3', 'broadcastable', 'master_copy'];
    }
}
