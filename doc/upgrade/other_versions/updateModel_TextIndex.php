#!/usr/bin/env php
<?php
// application.php

set_time_limit(0);

require __DIR__.'/../app/autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Console\Helper\ProgressBar;

class UpgradePumukitCommand extends ContainerAwareCommand
{
    private $dm;

    protected function configure()
    {
        $this
            ->setName('update:model:textindex')
            ->setDescription('Update TextIndex in Multimedia Objects')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updateSeriesTitleInMultimediaObjects($output);
        $output->writeln('');
        $output->writeln('Mongo MultimediaObject and Series collection updated TextIndex field.');
    }

    /**
     * NOTE: This function is to update the seriesTitle field in each
     *       MultimediaObject for MongoDB Search Index purposes.
     *       Do not modify it.
     */
    protected function updateSeriesTitleInMultimediaObjects(OutputInterface $output)
    {
        $ii = 0;
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $serviceMultimediaObject = $this->getContainer()->get('pumukitschema.schema.multimediaobject');
        $serviceSeries = $this->getContainer()->get('pumukitschema.schema.series');

        $mmRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $dm->getRepository('PumukitSchemaBundle:Series');

        $multimediaObjects = $mmRepo->createQueryBuilder()->getQuery()->execute();

        $progress = new ProgressBar($output, count($multimediaObjects));
        $progress->setFormat('verbose');
        $output->writeln('Updating Multimedia Objects...');
        $progress->start();
        foreach ($multimediaObjects as $multimediaObject) {
            ++$ii;
            $progress->advance();
            $serviceMultimediaObject->updateTextIndex($multimediaObject);
            if ($ii % 20) {
                $dm->flush();
                $dm->clear();
            }
        }
        $dm->flush();
        $dm->clear();

        $series = $seriesRepo->createQueryBuilder()->getQuery()->execute();
        $progress = new ProgressBar($output, count($series));
        $progress->setFormat('verbose');
        $output->writeln('');
        $output->writeln('Updating Series...');
        $progress->start();
        foreach ($series as $serie) {
            ++$ii;
            $progress->advance();
            $serviceSeries->updateTextIndex($serie);
            if ($ii % 20) {
                $dm->flush();
                $dm->clear();
            }
        }
        $dm->flush();
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
