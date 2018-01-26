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
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class UpgradePumukitCommand extends ContainerAwareCommand
{
    private $dm;

    protected function configure()
    {
        $this
            ->setName('update:model:2.3to2.4')
            ->setDescription('Update the documents (from 2.3) to match the 2.4 version.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('************* Update model from 2.3.x to 2.4.x ***************');
        $seriesMessage = $this->initSeriesNewFields();
        $output->writeln($seriesMessage);
        $multimediaObjectMessages = $this->initMultimediaObjectNewFields();
        foreach ($multimediaObjectMessages as $message) {
            $output->writeln($message);
        }
        $output->writeln('End updating model.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
    }

    protected function initSeriesNewFields()
    {
        $all = $this->dm->createQueryBuilder('PumukitSchemaBundle:Series')
            ->update()
            ->multiple(true)
            ->field('hide')->set(false)
            ->field('sorting')->set(Series::SORT_MANUAL)
            ->getQuery()
            ->execute();
        return 'Series.hide initialized to false and Series.sorting to SORT_MANUAL (ascendent rank): Modified '.$all['nModified'].' serie(s)';
    }

    protected function initMultimediaObjectNewFields()
    {
        $messages = array();

        $all = $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
            ->update()
            ->multiple(true)
            ->field('islive')->set(false)
            ->field('type')->set(MultimediaObject::TYPE_UNKNOWN)
            ->getQuery()
            ->execute();
        $messages[] = 'MultimediaObject.type initialized to TYPE_UNKNOWN (0): Modified '.$all['nModified'].' object(s)';

        $qb = $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject');
        $qb->field('tracks')->elemMatch($qb->expr()->field('tags')->equals('master')->field('only_audio')->equals(false));
        $video = $qb->update()
            ->multiple(true)
            ->field('type')->set(MultimediaObject::TYPE_VIDEO)
            ->getQuery()
            ->execute();
        $messages[] = 'MultimediaObject.type with master tracks without only audio initialized to TYPE_VIDEO (1): Modified '.$video['nModified'].' object(s)';

        $external = $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
            ->field('properties.opencast')->exists(true)
            ->update()
            ->multiple(true)
            ->field('type')->set(MultimediaObject::TYPE_VIDEO)
            ->getQuery()
            ->execute();
        $messages[] = 'MultimediaObject.type with properties.opencast initialized to TYPE_VIDEO (1): Modified '.$external['nModified'].' object(s)';

        $qb = $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject');
        $qb->field('tracks')->elemMatch($qb->expr()->field('tags')->equals('master')->field('only_audio')->equals(true));
        $audio = $qb->update()
            ->multiple(true)
            ->field('type')->set(MultimediaObject::TYPE_AUDIO)
            ->getQuery()
            ->execute();
        $messages[] = 'MultimediaObject.type with master tracks with only audio initialized to TYPE_AUDIO (2): Modified '.$audio['nModified'].' object(s)';

        $external = $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
            ->field('properties.externalplayer')->exists(true)
            ->field('properties.externalplayer')->notEqual('')
            ->update()
            ->multiple(true)
            ->field('type')->set(MultimediaObject::TYPE_EXTERNAL)
            ->getQuery()
            ->execute();
        $messages[] = 'MultimediaObject.type with properties.externalplayer not empty initialized to TYPE_EXTERNAL (3): Modified '.$external['nModified'].' object(s)';

        return $messages;
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
