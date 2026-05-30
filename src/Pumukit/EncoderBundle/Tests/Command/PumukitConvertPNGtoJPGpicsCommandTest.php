<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Command;

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
class PumukitConvertPNGtoJPGpicsCommandTest extends PumukitTestCase
{
    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
    }

    public function testSurvivesPicWithoutPath(): void
    {
        $pic = new Pic();
        $pic->setUrl('http://example.com/pic.png');
        $pic->setTags(['auto', 'frame_5']);

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setTitle('regenerate-pics-test');
        $multimediaObject->addPic($pic);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $tester = $this->commandTester();
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:regenerate:pics');

        return new CommandTester($command);
    }
}
