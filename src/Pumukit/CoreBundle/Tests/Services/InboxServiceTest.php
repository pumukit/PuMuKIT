<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\Services\InboxService;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[CoversNothing]
class InboxServiceTest extends TestCase
{
    private string $root;
    private string $inbox;
    private InboxService $inboxService;

    public function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/pumukit-inbox-'.uniqid('', true);
        $this->inbox = $this->root.'/inbox';
        mkdir($this->inbox.'/my folder', 0777, true);
        mkdir($this->root.'/private');
        touch($this->inbox.'/root.mp4');
        touch($this->inbox.'/my folder/video.mp4');
        touch($this->root.'/private/master.mp4');
        symlink($this->root.'/private/master.mp4', $this->inbox.'/my folder/link.mp4');

        $this->inboxService = new InboxService('https://localhost/tus', 1, '0MB', '1GB', 10, $this->inbox, false, false, '#000', false);
    }

    public function tearDown(): void
    {
        (new Filesystem())->remove($this->root);
    }

    public function testFindsTheFileInTheSeriesFolder(): void
    {
        $this->assertSame(realpath($this->inbox.'/my folder/video.mp4'), $this->inboxService->uploadedFilePath('video.mp4', 'my folder'));
    }

    public function testFallsBackToTheInboxRoot(): void
    {
        $this->assertSame(realpath($this->inbox.'/root.mp4'), $this->inboxService->uploadedFilePath('root.mp4', '5f0000000000000000000001'));
    }

    public function testUsesTheSameFolderAsTheTusUpload(): void
    {
        mkdir($this->inbox.'/Fsica');
        touch($this->inbox.'/Fsica/clase.mp4');

        $this->assertSame('Fsica', $this->inboxService->folderName('Física'));
        $this->assertSame(realpath($this->inbox.'/Fsica/clase.mp4'), $this->inboxService->uploadedFilePath('clase.mp4', 'Física'));
    }

    #[DataProvider('filesOutsideTheInbox')]
    public function testRejectsFilesOutsideTheInbox(string $fileName, string $folder): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->inboxService->uploadedFilePath($fileName, $folder);
    }

    public static function filesOutsideTheInbox(): array
    {
        return [
            'parent directory' => ['../private/master.mp4', 'my folder'],
            'nested traversal' => ['../../private/master.mp4', 'my folder'],
            'absolute path' => ['/etc/passwd', 'my folder'],
            'traversal in the folder' => ['master.mp4', '../private'],
            'symlink to a file outside' => ['link.mp4', 'my folder'],
            'current directory' => ['.', 'my folder'],
            'parent' => ['..', 'my folder'],
            'empty name' => ['', 'my folder'],
            'null byte' => ["video.mp4\0.txt", 'my folder'],
            'missing file' => ['missing.mp4', 'my folder'],
            'directory' => ['my folder', ''],
        ];
    }

    public function testRejectsFolderNamesWithoutValidCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->inboxService->folderName('../..');
    }
}
