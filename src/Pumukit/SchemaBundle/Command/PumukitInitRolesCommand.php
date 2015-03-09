<?php

namespace Pumukit\SchemaBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Role;

class PumukitInitRolesCommand extends ContainerAwareCommand
{
    private $dm = null;
    private $repo = null;

    private $rolesPath = "../Resources/data/roles/";

    protected function configure()
    {
        $this
            ->setName('pumukit:init:roles')
            ->setDescription('Load Pumukit initial roles to your database')
            ->addArgument('file', InputArgument::OPTIONAL, 'Input CSV path')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
          ->setHelp(<<<EOT

Command to load a controlled set of role initial data into a database. 
Useful for init Pumukit environment.

The --force parameter has to be used to actually drop the database.

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository("PumukitSchemaBundle:Role");

        if ($input->getOption('force')) {
            $finder = new Finder();
            $finder->files()->in(__DIR__.'/'.$this->rolesPath);
            $file = $input->getArgument('file');
            if ((0 == strcmp($file, "")) && (!$finder)) {
                $output->writeln("<error>There's no data to initialize</error>");

                return -1;
            }
            $this->removeRoles();
            foreach ($finder as $roleFile) {
                $this->createFromFile($roleFile, $output);
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

    protected function removeRoles()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Role')->remove(array());
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
                        $role = $this->createRoleFromCsvArray($currentRow);
                        $idCodMapping[$currentRow[0]] = $role;
                        $output->writeln("Role persisted - new id: ".$role->getId()." code: ".$role->getCod());
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
     * Create Role from CSV array
     */
    private function createRoleFromCsvArray($csv_array)
    {
        $role = new Role();

        $role->setCod($csv_array[1]);
        $role->setXml($csv_array[2]);
        $role->setDisplay($csv_array[3]);
        $role->setName($csv_array[4], 'es');
        // NOTE Take care of csv language order!
        if (isset($csv_array[5])) {
            $role->setName($csv_array[5], 'gl');
        }
        if (isset($csv_array[6])) {
            $role->setName($csv_array[6], 'en');
        }
        $role->setText($csv_array[7], 'es');
        // NOTE Take care of csv language order!
        if (isset($csv_array[8])) {
            $role->setText($csv_array[8], 'gl');
        }
        if (isset($csv_array[9])) {
            $role->setText($csv_array[9], 'en');
        }

        $this->dm->persist($role);

        return $role;
    }
}