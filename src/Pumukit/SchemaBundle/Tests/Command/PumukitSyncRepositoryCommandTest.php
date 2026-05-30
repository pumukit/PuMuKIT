<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Command;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversNothing
 */
class PumukitSyncRepositoryCommandTest extends PumukitTestCase
{
    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
    }

    public function testExecutingJobCounterDoesNotTriggerSpuriousCleanup(): void
    {
        $executingJob = new Job();
        $executingJob->setMmId('mmobj-id');
        $executingJob->setProfile('master_copy');
        $executingJob->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($executingJob);
        $this->dm->flush();

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setTitle('sync-repo-test');
        $multimediaObject->setProperty('executing_jobs', [$executingJob->getId()]);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $tester = $this->commandTester();
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $this->dm->clear();
        $reloaded = $this->dm->getRepository(MultimediaObject::class)->find($multimediaObject->getId());
        $this->assertNotNull(
            $reloaded->getProperty('executing_jobs'),
            'executing_jobs property must survive when EXECUTING jobs counter matches mmobj entries',
        );
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:sync:repository');

        return new CommandTester($command);
    }
}
