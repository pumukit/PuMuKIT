<?php

namespace Pumukit\EncoderBundle\Command;

use Pumukit\EncoderBundle\Document\Job;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PumukitEncoderInfoCommand extends BasePumukitEncoderCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:encoder:info')
            ->setDescription('Pumukit show job info')
            ->setDefinition([
                new InputArgument('id', InputArgument::OPTIONAL, 'Job identifier to execute'),
                new InputOption('all', null, InputOption::VALUE_NONE, 'Set this parameter to list jobs in all states'),
            ])
            ->setHelp(
                <<<'EOT'
EOT
          )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobService = $this->getContainer()->get('pumukitencoder.job');
        $jobService->executeNextJob();

        if ($input->getArgument('id')) {
            $this->showInfo($input->getArgument('id'), $output);
        } else {
            $this->showList($input->getOption('all'), $output);
        }
    }

    protected function showList($all, OutputInterface $output)
    {
        $this->listCpus($output);
        $this->listJobs($output, $all);
    }

    private function listCpus(OutputInterface $output)
    {
        $cpuService = $this->getContainer()->get('pumukitencoder.cpu');
        $deactivatedCpus = $cpuService->getCpuNamesInMaintenanceMode();
        $cpus = $cpuService->getCpus();

        $output->writeln('<info>CPUS:</info>');
        $table = new Table($output);
        $table->setHeaders(['Name', 'Status', 'Type', 'Host', 'Number', 'Description']);

        foreach ($cpus as $name => $cpu) {
            $table->addRow([
                $name,
                in_array($name, $deactivatedCpus) ?
                    '<error>In Maintenance</error>' :
                    '<info>Working</info>',
                $cpu['type'],
                $cpu['host'],
                $cpu['number'].'/'.$cpu['max'],
                $cpu['description'],
            ]);
        }
        $table->render();
    }

    private function listJobs(OutputInterface $output, $all = false)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $jobRepo = $dm->getRepository(Job::class);
        $jobService = $this->getContainer()->get('pumukitencoder.job');

        $stats = $jobService->getAllJobsStatus();

        $output->writeln('<info>JOBS NUMBERS:</info>');
        $table = new Table($output);
        $table->setHeaders(array_keys($stats));
        $table->addRow(array_values($stats));
        $table->render();

        if ($all) {
            $status = array_keys(Job::$statusTexts);
        } else {
            $status = [Job::STATUS_PAUSED, Job::STATUS_WAITING, Job::STATUS_EXECUTING, Job::STATUS_ERROR];
        }
        $sort = ['timeini' => 'asc'];
        $jobs = $jobRepo->findWithStatus($status, $sort);

        $output->writeln('<info>JOBS:</info>');
        $table = new Table($output);
        $table->setHeaders(['Id', 'Status', 'MM', 'Profile', 'Cpu', 'Priority',
            'Timeini', 'Timestart', 'Timeend', ]);

        foreach ($jobs as $name => $job) {
            $table->addRow([
                $job->getId(),
                $this->formatStatus($job->getStatus()),
                $job->getMmId(),
                $job->getProfile(),
                $job->getCpu(),
                $job->getPriority(),
                $job->getTimeini('Y-m-d H:i:s'),
                $job->getTimestart('Y-m-d H:i:s'),
                $job->getTimeend('Y-m-d H:i:s'),
            ]);
        }
        $table->render();
    }

    private function showInfo($id, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $jobService = $this->getContainer()->get('pumukitencoder.job');

        if (null === ($job = $dm->find(Job::class, $id))) {
            throw new \RuntimeException("Not job found with id {$id}.");
        }

        if (null === ($job = $dm->find(Job::class, $id))) {
            throw new \RuntimeException("Not job found with id {$id}.");
        }

        $output->writeln('<comment>Id</comment>                '.$job->getId());
        $output->writeln('<comment>Status</comment>            '.$this->formatStatus($job->getStatus()));
        $output->writeln('<comment>Mm</comment>                '.$job->getMmId());
        $output->writeln('<comment>Profile</comment>           '.$job->getProfile());
        $output->writeln('<comment>Cpu</comment>               '.$job->getCpu());
        $output->writeln('<comment>Priority</comment>          '.$job->getPriority());
        $output->writeln('<comment>Duration</comment>          '.$job->getDuration());
        $output->writeln('<comment>New Duration</comment>      '.$job->getNewDuration());
        $output->writeln('<comment>Timeini</comment>           '.$job->getTimeini('Y-m-d H:i:s'));
        $output->writeln('<comment>Timestart</comment>         '.$job->getTimestart('Y-m-d H:i:s'));
        $output->writeln('<comment>Timeend</comment>           '.$job->getTimeend('Y-m-d H:i:s'));
        $output->writeln('<comment>Command</comment>');
        $output->writeln($jobService->renderBat($job));
        $output->writeln('<comment>Out</comment>');
        $output->writeln($job->getOutput());
    }
}
