<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobExecutor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PumukitEncoderExecuteCommand extends Command
{
    private $dm;
    private $jobExecutor;

    public function __construct(DocumentManager $documentManager, JobExecutor $jobExecutor)
    {
        $this->dm = $documentManager;
        $this->jobExecutor = $jobExecutor;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:encoder:job')
            ->setDescription('Execute an encoder job by id. Spawned by JobExecutor; not intended for direct operator use.')
            ->addArgument('id', InputArgument::REQUIRED, 'Job identifier to execute')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (null === ($id = $input->getArgument('id'))) {
            throw new \RuntimeException("Argument 'ID' is required in order to execute this command correctly.");
        }

        if (null === ($job = $this->dm->getRepository(Job::class)->find($id))) {
            throw new \RuntimeException("Not job found with id {$id}.");
        }

        $this->executeJob($job);

        return Command::SUCCESS;
    }

    private function executeJob(Job $job): void
    {
        $this->jobExecutor->execute($job);
    }
}
