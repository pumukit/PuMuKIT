<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Command;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversNothing
 */
class CheckOrphansConsistencyCommandTest extends PumukitTestCase
{
    private string $tmpDir;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir().'/pumukit-orphans-'.uniqid('', true);
        mkdir($this->tmpDir);
    }

    public function tearDown(): void
    {
        if (isset($this->tmpDir) && is_dir($this->tmpDir)) {
            array_map('unlink', glob($this->tmpDir.'/*') ?: []);
            rmdir($this->tmpDir);
        }
        parent::tearDown();
    }

    public function testReportsFileThatHasNoDatabaseReference(): void
    {
        $orphan = $this->tmpDir.'/orphan.png';
        touch($orphan);

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString($orphan, $tester->getDisplay());
    }

    public function testDoesNotReportFileReferencedByPicPath(): void
    {
        $referenced = $this->tmpDir.'/referenced.png';
        touch($referenced);

        $pic = new Pic();
        $pic->setPath($referenced);
        $pic->setUrl('http://example.com/referenced.png');

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setTitle('orphans-test');
        $multimediaObject->addPic($pic);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringNotContainsString('Orphan file: '.$referenced, $tester->getDisplay());
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:check:orphans');

        return new CommandTester($command);
    }
}
