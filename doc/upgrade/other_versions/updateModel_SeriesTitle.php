#!/usr/bin/env php
<?php
// application.php

set_time_limit(0);

require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Debug\Debug;
use Pumukit\SchemaBundle\Document\Person;

class UpdateModelSeriesTitleCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('update:model:seriestitle')
            ->setDescription('Update the documents to add the Series Title into the Multimedia Objects.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updateSeriesTitleInMultimediaObjects();
        $output->writeln('Mongo MultimediaObject collection updated with SeriesTitle field.');
    }

    /**
     * NOTE: This function is to update the seriesTitle field in each
     *       MultimediaObject for MongoDB Search Index purposes.
     *       Do not modify it.
     */
    protected function updateSeriesTitleInMultimediaObjects()
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $mmRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');

        $multimediaObjects = $mmRepo->findAll();
        foreach ($multimediaObjects as $multimediaObject) {
            $series = $multimediaObject->getSeries();
            $multimediaObject->setSeries($series);
            $dm->persist($multimediaObject);
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
$application->add(new UpdateModelSeriesTitleCommand());
$application->run();
