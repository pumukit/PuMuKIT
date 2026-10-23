<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Twig;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Services\InboxService;
use Pumukit\CoreBundle\Twig\UploaderExtension;
use Pumukit\CoreBundle\Utils\MediaMimeTypeUtils;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
#[CoversNothing]
class UploaderExtensionTest extends KernelTestCase
{
    private UploaderExtension $extension;
    private InboxService $inboxService;

    public function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        $this->extension = static::getContainer()->get(UploaderExtension::class);
        $this->inboxService = static::getContainer()->get(InboxService::class);
    }

    public function testDefaultsComeFromTheInboxConfiguration(): void
    {
        $options = $this->extension->options();

        $this->assertSame('tus', $options['transport']);
        $this->assertSame($this->inboxService->inboxUploadURL(), $options['endpoint']);
        $this->assertTrue($options['autoProceed']);
        $this->assertSame(UploaderExtension::sizeToBytes($this->inboxService->maxFileSize()), $options['restrictions']['maxFileSize']);
        $this->assertSame($this->inboxService->maxNumberOfFiles(), $options['restrictions']['maxNumberOfFiles']);
        $this->assertSame(MediaMimeTypeUtils::allowedMimeTypes(), $options['restrictions']['allowedFileTypes']);
        $this->assertSame($this->inboxService->overridePatchMethod(), $options['tus']['overridePatchMethod']);
        $this->assertSame($this->inboxService->inboxUploadLIMIT(), $options['tus']['limit']);
        $this->assertNull($options['import']);
        $this->assertCount(2, $options['styles']);
        $this->assertMatchesRegularExpression('#^/assets/vendor/@uppy/core/dist/style\.min-[\w-]+\.css$#', $options['styles'][0]);
        $this->assertMatchesRegularExpression('#^/assets/vendor/@uppy/dashboard/dist/style\.min-[\w-]+\.css$#', $options['styles'][1]);
    }

    public function testXhrUploadsDoNotStartOnTheirOwn(): void
    {
        $options = $this->extension->options(['transport' => 'xhr', 'endpoint' => '/upload']);

        $this->assertSame('/upload', $options['endpoint']);
        $this->assertFalse($options['autoProceed']);
    }

    public function testNestedOptionsReplaceOnlyTheGivenKeys(): void
    {
        $options = $this->extension->options([
            'restrictions' => ['maxNumberOfFiles' => 1, 'allowedFileTypes' => ['audio/*']],
            'xhr' => ['fieldName' => 'resource[]'],
        ]);

        $this->assertSame(1, $options['restrictions']['maxNumberOfFiles']);
        $this->assertSame(['audio/*'], $options['restrictions']['allowedFileTypes']);
        $this->assertSame(UploaderExtension::sizeToBytes($this->inboxService->minFileSize()), $options['restrictions']['minFileSize']);
        $this->assertSame('resource[]', $options['xhr']['fieldName']);
        $this->assertSame(1800000, $options['xhr']['timeout']);
    }

    public function testAttributesAreEscapedAndCarryTheOptions(): void
    {
        $attributes = $this->extension->attributes(['meta' => ['series' => '"><script>alert(1)</script>']]);

        $this->assertMatchesRegularExpression('/^data-controller="core--uploader" data-core--uploader-options-value="[^"<>]*"$/', $attributes);

        preg_match('/data-core--uploader-options-value="([^"]*)"/', $attributes, $matches);
        $options = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('"><script>alert(1)</script>', $options['meta']['series']);
    }

    public function testAllowedTypesFollowTheMultimediaObjectType(): void
    {
        $multimediaObject = new MultimediaObject();
        $this->assertSame(MediaMimeTypeUtils::allowedMimeTypes(), $this->extension->allowedTypes($multimediaObject));

        $multimediaObject->setType(MultimediaObject::TYPE_AUDIO);
        $this->assertSame(MediaMimeTypeUtils::allowedAudioMimeTypes(), $this->extension->allowedTypes($multimediaObject));

        $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
        $this->assertSame(MediaMimeTypeUtils::allowedVideoMimeTypes(), $this->extension->allowedTypes($multimediaObject));
    }

    public function testXhrUploadsAreLimitedByPhp(): void
    {
        $options = $this->extension->options(['transport' => 'xhr', 'endpoint' => '/upload']);
        $limits = array_filter([UploaderExtension::iniSizeToBytes((string) ini_get('upload_max_filesize')), UploaderExtension::iniSizeToBytes((string) ini_get('post_max_size'))]);

        $this->assertSame(min([UploaderExtension::sizeToBytes($this->inboxService->maxFileSize()), ...$limits]), $options['restrictions']['maxFileSize']);
        $this->assertSame(['Accept' => 'application/json'], $options['xhr']['headers']);
        $this->assertSame(10 * 1024 * 1024 * 1024, UploaderExtension::iniSizeToBytes('10G'));
        $this->assertSame(8 * 1024 * 1024, UploaderExtension::iniSizeToBytes('8M'));
        $this->assertSame(0, UploaderExtension::iniSizeToBytes('0'));
    }

    public function testSizesKeepTheUnitsOfTheConfiguration(): void
    {
        $this->assertSame(0, UploaderExtension::sizeToBytes('0MB'));
        $this->assertSame(20 * 1024 * 1024 * 1024, UploaderExtension::sizeToBytes('20GB'));
        $this->assertSame(512 * 1024 * 1024, UploaderExtension::sizeToBytes('512MB'));
        $this->assertSame(1024 * 1024 * 1024, UploaderExtension::sizeToBytes('100'));
    }
}
