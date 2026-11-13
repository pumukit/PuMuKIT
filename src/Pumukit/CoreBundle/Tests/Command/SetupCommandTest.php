<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Command\SetupCommand;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Console\Application as KernelApplication;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversNothing]
class SetupCommandTest extends PumukitTestCase
{
    public function testItSetsUpAnEmptyDatabaseAndCanRunAgain(): void
    {
        $options = ['--user' => 'setup-admin', '--email' => 'setup-admin@example.com', '--password' => 'setup-password'];

        $tester = $this->commandTester();
        $tester->execute($options);
        $this->assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());

        $tags = count($this->dm->getRepository(Tag::class)->findAll());
        $this->assertNotNull($this->dm->getRepository(Tag::class)->findOneBy(['cod' => 'ROOT']));
        $this->assertSame(1, count($this->dm->getRepository(User::class)->findBy(['username' => 'setup-admin'])));

        $tester = $this->commandTester();
        $tester->execute($options);
        $this->assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
        $this->assertStringContainsString('The user "setup-admin" already exists.', $tester->getDisplay());

        $this->dm->clear();
        $this->assertSame($tags, count($this->dm->getRepository(Tag::class)->findAll()));
        $this->assertSame(1, count($this->dm->getRepository(User::class)->findBy(['username' => 'setup-admin'])));
    }

    public function testTheUserNeedsAnEmailAndAPassword(): void
    {
        $tester = $this->commandTester();
        $tester->execute(['--user' => 'setup-admin']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertSame(0, count($this->dm->getRepository(User::class)->findBy(['username' => 'setup-admin'])));
    }

    public function testItRunsTheBundleStepsAfterTheCoreAndStopsOnAFailure(): void
    {
        $calls = new \ArrayObject();
        $application = new Application();
        foreach (['doctrine:mongodb:schema:update', 'pumukit:init:repo', 'bundle:first', 'bundle:failing', 'bundle:last'] as $name) {
            $application->addCommand($this->recordingCommand($name, $calls, 'bundle:failing' === $name ? 7 : Command::SUCCESS));
        }

        $command = new SetupCommand(
            $this->createStub(DocumentManager::class),
            [$application->find('bundle:first'), $application->find('bundle:failing'), $application->find('bundle:last')],
        );
        $application->addCommand($command);

        $tester = new CommandTester($application->find('pumukit:setup'));
        $tester->execute([]);

        $this->assertSame(7, $tester->getStatusCode());
        $this->assertSame(['doctrine:mongodb:schema:update', 'pumukit:init:repo', 'bundle:first', 'bundle:failing'], $calls->getArrayCopy());
    }

    private function commandTester(): CommandTester
    {
        return new CommandTester((new KernelApplication(self::$kernel))->find('pumukit:setup'));
    }

    private function recordingCommand(string $name, \ArrayObject $calls, int $status): Command
    {
        return new class($name, $calls, $status) extends Command {
            public function __construct(string $name, private readonly \ArrayObject $calls, private readonly int $status)
            {
                parent::__construct($name);
                $this->addArgument('repo');
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $this->calls[] = $this->getName();

                return $this->status;
            }
        };
    }
}
