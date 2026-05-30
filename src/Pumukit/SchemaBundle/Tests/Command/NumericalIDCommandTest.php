<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Command;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversNothing
 */
class NumericalIDCommandTest extends PumukitTestCase
{
    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
    }

    public function testGenerateStepOnEmptyDatabaseDoesNotThrow(): void
    {
        $tester = $this->commandTester();
        $tester->execute(['--step' => 'generate']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testGenerateStepAssignsNumericalIdToMultimediaObjectWithoutOne(): void
    {
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle('numerical-id-test');
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $tester = $this->commandTester();
        $tester->execute(['--step' => 'generate']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $this->dm->clear();
        $reloaded = $this->dm->getRepository(MultimediaObject::class)->find($multimediaObject->getId());
        $this->assertGreaterThanOrEqual(1, $reloaded->getNumericalID());
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:update:numerical:id');

        return new CommandTester($command);
    }
}
