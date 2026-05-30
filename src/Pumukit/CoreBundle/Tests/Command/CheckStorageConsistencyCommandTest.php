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
class CheckStorageConsistencyCommandTest extends PumukitTestCase
{
    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
    }

    public function testReportsPicWithMissingPhysicalFile(): void
    {
        $missing = '/nonexistent/path/missing-pic.jpg';
        $this->seedMultimediaObjectWithPic($missing);

        $tester = $this->commandTester();
        $tester->execute(['--pics' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString($missing, $tester->getDisplay());
    }

    public function testDoesNotReportPicWithExistingPhysicalFile(): void
    {
        $existing = sys_get_temp_dir().'/pumukit-storage-existing-'.uniqid('', true).'.jpg';
        touch($existing);

        try {
            $this->seedMultimediaObjectWithPic($existing);

            $tester = $this->commandTester();
            $tester->execute(['--pics' => true]);

            $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
            $this->assertStringNotContainsString($existing, $tester->getDisplay());
        } finally {
            unlink($existing);
        }
    }

    private function seedMultimediaObjectWithPic(string $path): void
    {
        $pic = new Pic();
        $pic->setPath($path);
        $pic->setUrl('http://example.com/pic.jpg');

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setTitle('check-storage-test');
        $multimediaObject->addPic($pic);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:check:storage');

        return new CommandTester($command);
    }
}
