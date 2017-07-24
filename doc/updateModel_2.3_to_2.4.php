#!/usr/bin/env php
<?php
// application.php

set_time_limit(0);

require __DIR__ . '/../app/autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;

class UpgradePumukitCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('update:model:2.3to2.4')
            ->setDescription('Update the documents (from 2.3) to match the 2.4 version.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initSeriesHide();
        $output->writeln('Series.hide initialed to false');
    }

    protected function initSeriesHide()
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $dm->createQueryBuilder('PumukitSchemaBundle:Series')
            ->update()
            ->multiple(true)
            ->field('hide')->set(false)
            ->getQuery()
            ->execute();
    }
}

$input = new ArgvInput();
$env = $input->getParameterOption(array('--env', '-e'), getenv('SYMFONY_ENV') ?: 'dev');
$debug = getenv('SYMFONY_DEBUG') !== '0' && !$input->hasParameterOption(array('--no-debug', '')) && $env !== 'prod';

if ($debug) {
    Debug::enable();
}

$kernel = new AppKernel($env, $debug);
$application = new Application($kernel);
$application->add(new UpgradePumukitCommand());
$application->run();
