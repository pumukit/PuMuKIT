<?php

namespace Pumukit\SchemaBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Broadcast;

class PumukitInitBroadcastsCommand extends ContainerAwareCommand
{
    private $dm = null;
    private $repo = null;

    private $broadcastsPath = "../Resources/data/broadcasts/";

    protected function configure()
    {
        $this
            ->setName('pumukit:init:broadcasts')
            ->setDescription('Load Pumukit initial broadcasts to your database')
            ->addArgument('file', InputArgument::OPTIONAL, 'Input CSV path')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
          ->setHelp(<<<EOT

Command to load a controlled set of broadcast initial data into a database. 
Useful for init Pumukit environment.

The --force parameter has to be used to actually drop the database.

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository("PumukitSchemaBundle:Broadcast");

        if ($input->getOption('force')) {
            $finder = new Finder();
            $finder->files()->in(__DIR__.'/'.$this->broadcastsPath);
            $file = $input->getArgument('file');
            if ((0 == strcmp($file, "")) && (!$finder)) {
                $output->writeln("<error>There's no data to initialize</error>");

                return -1;
            }
            $this->removeBroadcasts();
            foreach ($finder as $broadcastFile) {
                $this->createFromFile($broadcastFile, $output);
            }
            if ($file) {
                $this->createFromFile($file, $output);
            }
        } else {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
            $output->writeln('');
            $output->writeln('<info>Would drop the database</info>');
            $output->writeln('Please run the operation with --force to execute');
            $output->writeln('<error>All data will be lost!</error>');

            return -1;
        }
    }

    protected function removeBroadcasts()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')->remove(array());
    }

    protected function createFromFile($file, OutputInterface $output)
    {
        if (!file_exists($file)) {
            $output->writeln("<error>Error stating ".$file."</error>");

            return -1;
        }

        $idCodMapping = array();

        $row = 1;
        if (($file = fopen($file, "r")) !== false) {
            while (($currentRow = fgetcsv($file, 300, ";")) !== false) {
                $number = count($currentRow);
                if ($number == 10) {
                    //Check header rows
                    if (trim($currentRow[0]) == "id") {
                        continue;
                    }

                    try {
                        $broadcast = $this->createBroadcastFromCsvArray($currentRow);
                        $idCodMapping[$currentRow[0]] = $broadcast;
                        $output->writeln("Broadcast persisted - new id: ".$broadcast->getId()." type: ".$broadcast->getBroadcastTypeId());
                    } catch (Exception $e) {
                        $output->writeln("<error>".$e->getMessage()."</error>");
                    }
                } else {
                    $output->writeln("Last valid row = ...");
                    $output->writeln("Error: line $row has $number elements");
                }
                
                if ($row % 100 == 0) {
                    echo "Row ".$row."\n";
                }
                $previous_content = $currentRow;
                $row++;
            }
            fclose($file);
            $this->dm->flush();
        } else {
            $output->writeln("<error>Error opening ".$file."</error>");

            return -1;
        }
    }

    /**
     * Create Broadcast from CSV array
     */
    private function createBroadcastFromCsvArray($csv_array)
    {
        $broadcast = new Broadcast();

        $broadcast->setName($csv_array[1], 'es');
        // NOTE Take care of csv language order!
        if (isset($csv_array[2])) {
            $broadcast->setName($csv_array[2], 'gl');
        }
        if (isset($csv_array[3])) {
            $broadcast->setName($csv_array[3], 'en');
        }
        if (in_array($csv_array[4], array(Broadcast::BROADCAST_TYPE_PUB, Broadcast::BROADCAST_TYPE_PRI, Broadcast::BROADCAST_TYPE_COR))){
            $broadcast->setBroadcastTypeId($csv_array[4]);
        }else{
            $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PRI);
        }
        $broadcast->setPasswd($csv_array[5]);
        $broadcast->setDefaultSel($csv_array[6]);
        $broadcast->setDescription($csv_array[7], 'es');
        // NOTE Take care of csv language order!
        if (isset($csv_array[8])) {
            $broadcast->setDescription($csv_array[8], 'gl');
        }
        if (isset($csv_array[9])) {
            $broadcast->setDescription($csv_array[9], 'en');
        }
        
        $this->dm->persist($broadcast);

        return $broadcast;
    }
}