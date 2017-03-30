#!/usr/bin/env php
<?php
// application.php

set_time_limit(0);

require __DIR__.'/../app/autoload.php';

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Broadcast;

class UpgradePumukitCommand extends ContainerAwareCommand
{
    private $dm;
    private $typeLoginName = 'Private (LDAP)';

    protected function configure()
    {
        $this
            ->setName('update:model:2.2to2.3')
            ->setDescription('Update the documents (from 2.2) to match the 2.3 version.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initParameters();

        $output->writeln(' ***** Updating PuMuKIT 2.2 to PuMuKIT 2.3 ***** ');

        $this->updateKeywords();
        $output->writeln('Series and MultimediaObject collections updated (keywords)');

        $this->updateBroadcast();
        $output->writeln('MultimediaObject collection updated (broadcasts)');
    }

    private function initParameters()
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
    }

    private function updateKeywords()
    {
        $addKeywordsSeries = 'db.Series.update({},{$set: {"keywords": {}}},{multi:true})';
        $addKeywordsMMO = 'db.MultimediaObject.update({},{$set: {"keywords": {}}},{multi:true})';
        $updateSeries = "db.Series.find().forEach(function(u){
                                    for(language in u.keyword) {
                                        if(u.keyword[language] !=null && typeof(u.keyword[language]) != 'undefined') {
                                            var s = u.keyword[language].split(\",\");
                                            u.keywords[language] = s.map(Function.prototype.call, String.prototype.trim);
                                        }
                                    }
                                    db.Series.save(u);
                                })";

        $updateMultimediaObjects = "db.MultimediaObject.find().forEach(function(u){
                                            for(language in u.keyword) {
                                                if(u.keyword[language] !=null && typeof(u.keyword[language]) != 'undefined') {
                                                    var s = u.keyword[language].split(\",\");
                                                    u.keywords[language] = s.map(Function.prototype.call, String.prototype.trim);
                                                }
                                            }
                                            db.MultimediaObject.save(u);
                                        })";

        $dbs = $this->getContainer()->getParameter('mongodb_database');
        try {
            $this->dm->getConnection()->getMongoClient()->$dbs->execute($addKeywordsSeries);
            $this->dm->getConnection()->getMongoClient()->$dbs->execute($addKeywordsMMO);
            $this->dm->getConnection()->getMongoClient()->$dbs->execute($updateSeries);
            $this->dm->getConnection()->getMongoClient()->$dbs->execute($updateMultimediaObjects);
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    private function updateBroadcast()
    {
        try {
            $aMultimediaObjects = $this->getAllMultimediaObjects();
            if (count($aMultimediaObjects) > 0) {
                $this->convertBroadcastToEmbeddedBroadcast($aMultimediaObjects);
            }
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    private function getAllMultimediaObjects()
    {
        $aMultimediaObject = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findAll();

        return $aMultimediaObject;
    }

    /**
     * @param null $aMultimediaObjects
     */
    private function convertBroadcastToEmbeddedBroadcast($aMultimediaObjects = null)
    {
        if ($aMultimediaObjects) {
            foreach ($aMultimediaObjects as $oMultimedia) {
                $this->createEmbeddedBroadcast($oMultimedia);
            }
        }
    }

    /**
     * @param $oMultimedia
     */
    private function createEmbeddedBroadcast($oMultimedia)
    {
        if ($oBroadcast = $oMultimedia->getBroadcast()) {
            switch ($oBroadcast->getBroadcastTypeId()) {
                case Broadcast::BROADCAST_TYPE_PUB:
                    $sName = EmbeddedBroadcast::NAME_PUBLIC;
                    $sType = EmbeddedBroadcast::TYPE_PUBLIC;
                    break;
                case Broadcast::BROADCAST_TYPE_PRI:
                    $sName = EmbeddedBroadcast::NAME_PASSWORD;
                    $sType = EmbeddedBroadcast::TYPE_PASSWORD;
                    break;
                case Broadcast::BROADCAST_TYPE_COR:
                    if ($this->typeLoginName === $oBroadcast->getName()) {
                        $sName = EmbeddedBroadcast::NAME_LOGIN;
                        $sType = EmbeddedBroadcast::TYPE_LOGIN;
                    } elseif ($oBroadcast->getPasswd() && !empty($oBroadcast->getPasswd())) {
                        $sName = EmbeddedBroadcast::NAME_PASSWORD;
                        $sType = EmbeddedBroadcast::TYPE_PASSWORD;
                    }
                    break;
            }
        } else {
            $sName = EmbeddedBroadcast::NAME_PUBLIC;
            $sType = EmbeddedBroadcast::TYPE_PUBLIC;
        }

        $oEmbeddedBroadcast = new EmbeddedBroadcast();

        $oEmbeddedBroadcast->setType($sType);
        $oEmbeddedBroadcast->setName($sName);
        if (EmbeddedBroadcast::TYPE_PASSWORD === $sType) {
            $oEmbeddedBroadcast->setPassword($oBroadcast->getPasswd());
        }
        $this->dm->persist($oEmbeddedBroadcast);

        $oMultimedia->setEmbeddedBroadcast($oEmbeddedBroadcast);

        $this->dm->flush();
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
