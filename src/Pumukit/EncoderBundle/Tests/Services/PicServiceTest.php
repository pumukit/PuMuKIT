<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Services\PicService;

/**
 * @internal
 */
#[CoversNothing]
final class PicServiceTest extends PumukitTestCase
{
    private PicService $picService;

    public function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        parent::setUp();
        $this->picService = self::getContainer()->get(PicService::class);
    }

    public function testTheExtensionsAndTagsOfTheOptionsAreTrimmedAndWithoutEmptyValues(): void
    {
        [, , , $extensions, $tags] = $this->picService->formatInputs(null, null, null, 'jpg, png,', 'master , display', null, null);

        self::assertSame(['jpg', 'png'], $extensions);
        self::assertSame(['master', 'display'], $tags);
    }

    public function testOptionsWithOnlySeparatorsAreRefused(): void
    {
        $this->expectExceptionMessage('Please check extensions input');

        $this->picService->formatInputs(null, null, null, ' , ', null, null, null);
    }
}
