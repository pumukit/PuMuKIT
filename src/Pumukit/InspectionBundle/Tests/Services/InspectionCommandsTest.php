<?php

declare(strict_types=1);

namespace Pumukit\InspectionBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Pumukit\InspectionBundle\Services\InspectionDocumentService;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\InspectionBundle\Services\InspectionImageService;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[CoversNothing]
class InspectionCommandsTest extends TestCase
{
    private const FILE_NAMES = ['a b.mp4', 'a;b.mp4', 'a"b".mp4', "a'b.mp4", 'a$(b).mp4', 'a`b`.mp4', 'a&b.mp4'];

    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/'.uniqid('inspection', true);
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    #[DataProvider('fileNames')]
    public function testTheImageServiceReceivesTheFileAsOneArgument(string $name): void
    {
        $file = $this->file($name);
        $service = new InspectionImageService('printf %s "{{file}}"', new NullLogger());

        $this->assertSame($file, $service->getFileMetadata(Path::create($file)));
    }

    #[DataProvider('fileNames')]
    public function testTheDocumentServiceReceivesTheFileAsOneArgument(string $name): void
    {
        $file = $this->file($name);
        $service = new InspectionDocumentService('printf %s "{{file}}"', new NullLogger());

        $this->assertSame($file, $service->getFileMetadata(Path::create($file)));
    }

    #[DataProvider('fileNames')]
    public function testFfprobeReadsAFileWithTheConfiguredCommand(string $name): void
    {
        $service = new InspectionFfprobeService('ffprobe -v quiet -print_format json -show_format -show_streams {{file}}');

        $this->assertGreaterThan(0, $service->getDuration($this->file($name)));
    }

    #[DataProvider('fileNames')]
    public function testFfprobeReadsAFileWithTheDefaultCommand(string $name): void
    {
        $service = new InspectionFfprobeService();

        $this->assertGreaterThan(0, $service->getDuration($this->file($name)));
    }

    public static function fileNames(): iterable
    {
        foreach (self::FILE_NAMES as $name) {
            yield $name => [$name];
        }
    }

    #[DataProvider('servicesWithAnOptionalLogger')]
    public function testAFailedCommandWithoutALoggerReturnsNoMetadata(string $class): void
    {
        $service = new $class('false "{{file}}"');

        $this->assertSame('', $service->getFileMetadata(Path::create($this->file('a.mp4'))));
    }

    public static function servicesWithAnOptionalLogger(): iterable
    {
        yield 'image' => [InspectionImageService::class];

        yield 'document' => [InspectionDocumentService::class];
    }

    private function file(string $name): string
    {
        $file = $this->dir.'/'.$name;
        copy(dirname(__DIR__, 5).'/tests/files/pumukit.mp4', $file);

        return $file;
    }
}
