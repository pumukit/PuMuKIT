<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PumukitEncoderExecuteCommand extends Command
{
    private $dm;
    private $jobService;

    public function __construct(DocumentManager $documentManager, JobService $jobService)
    {
        $this->dm = $documentManager;
        $this->jobService = $jobService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:encoder:job')
            ->setDescription('Pumukit execute a encoder job')
            ->addArgument('id', InputArgument::REQUIRED, 'Job identifier to execute')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to re-execute jobs')
            ->setHelp(
                <<<'EOT'
The --force parameter ...

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === ($id = $input->getArgument('id'))) {
            throw new \RuntimeException("Argument 'ID' is required in order to execute this command correctly.");
        }

        if (null === ($job = $this->dm->getRepository(Job::class)->find($id))) {
            throw new \RuntimeException("Not job found with id {$id}.");
        }

        $this->executeJob($job);

        return 0;
    }

    private function executeJob(Job $job): void
    {
        $this->jobService->execute($job);
    }
}
