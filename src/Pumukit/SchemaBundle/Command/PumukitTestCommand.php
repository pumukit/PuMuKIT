<?php

namespace Pumukit\SchemaBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;


use Pumukit\SchemaBundle\Document\Tag;


class PumukitTestCommand extends ContainerAwareCommand
{

  private $dm = null;
  private $repo = null;

    protected function configure()
    {
        $this
            ->setName('pumukit:test')
            ->setDescription('Pumukit test command')
            ->addArgument('file', InputArgument::REQUIRED, 'Input CSV path')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<EOT
TODO

The --force parameter has to be used to actually drop the database.

EOT
	      );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
	$this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
	$this->repo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Tag");


	if ($input->getOption('force')) {
	  $file = $input->getArgument('file');
	  $this->removeTags();
	  $root = $this->createRoot();
	  $this->createFromFile($file, $root, $output);
	} else {
	  $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
	  $output->writeln('');
	  $output->writeln('<info>Would drop the database</info>');
	  $output->writeln('Please run the operation with --force to execute');
	  $output->writeln('<error>All data will be lost!</error>');

	  return -1;
	}
	
    }


    protected function removeTags()
    {
      $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')->remove(array());
    }

    protected function createRoot()
    {
      $root = $this->createTagFromCsvArray(array(null, "ROOT", 1, 0 , "ROOT", "ROOT", "ROOT"));
      $this->dm->flush();
      return $root;
    }

    protected function createFromFile($file, $root, OutputInterface $output)
    {
	if (!file_exists($file)) {
	  $stderr->writeln("<error>Error stating " . $file . "</error>");
	  return -1;
	}

	$idCodMapping = array();

	$row = 1;
	if (($file = fopen($file, "r")) !== false) {
	  while ( ($currentRow = fgetcsv($file, 300, ";")) !== false)  {
            $number = count($currentRow);
            if ($number == 6 || $number == 8){
	      //Check header rows
	      if (trim($currentRow[0]) == "id") { 
		continue;
	      }
	      
	      $parent = isset($idCodMapping[$currentRow[2]])
		? $idCodMapping[$currentRow[2]]
		: $root;

	      try {
		$tag = $this->createTagFromCsvArray($currentRow, $parent);
		$idCodMapping[$currentRow[0]] = $tag;
		$output->writeln("Tag persisted - new id: " . $tag->getId() . " cod: " . $tag->getCod());
	      } catch (Exception $e) {
		$output->writeln("<error>" . $e->getMessage() . "</error>");
	      }

            } else {
	      $stderr->writeln("Last valid row = ...");
	      $stderr->writeln("Error: line $row has $number elements");
            }

            if ($row % 100 == 0 ) echo "Row " . $row . "\n";
            $previous_content = $currentRow;
            $row++;
	  } 
	  fclose($file);
	  $this->dm->flush();

	} else {
	  $stderr->writeln("<error>Error opening " . $file . "</error>");
	  return -1;
	}
    }


    /**
     *
     */
    private function createTagFromCsvArray($csv_array, $cat_parent=null)
    {
      $c = new Tag();
      if ($tag = $this->repo->findOneByCod($csv_array[1])) {
	throw new \LengthException("Nothing done - Tag retrieved from DB id: " . $tag->getId() . " cod: " . $tag->getCod());
      }

      $tag = new Tag();
      $tag->setCod($csv_array[1]);
      $tag->setMetatag($csv_array[3]);
      $tag->setDisplay($csv_array[4]);
      if ($cat_parent) {
	$tag->setParent($cat_parent);
      }
      $tag->setTitle($csv_array[5], 'es');
      // TODO Take care of csv language order!
      if (isset($csv_array[6])){
	$tag->setTitle($csv_array[6], 'gl');
      }
      if (isset($csv_array[7])){
	$tag->setTitle($csv_array[7], 'en');
      }
      
      $this->dm->persist($tag);
      return $tag;
    }
}
