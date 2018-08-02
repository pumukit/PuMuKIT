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
//
use Symfony\Component\Console\Helper\ProgressBar;
use Pumukit\SchemaBundle\Document\MultimediaObject;


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
        $output->writeln('Mongo MultimediaObject collection updated TextIndex field.');
    }

    /**
     * NOTE: This function is to update the seriesTitle field in each
     *       MultimediaObject for MongoDB Search Index purposes.
     *       Do not modify it.
     */
    protected function updateSeriesTitleInMultimediaObjects(OutputInterface $output)
    {
        $service = $this->getContainer()->get('pumukitschema.schema.multimediaobject');
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $mmRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $multimediaObjects = $mmRepo->findAll();
        $progress = new ProgressBar($output, count($multimediaObjects));
        $progress->setFormat('verbose');
        $progress->start();
        foreach ($multimediaObjects as $multimediaObject) {
            $progress->advance();
            $service->updateTextIndex($multimediaObject);
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
