<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Pumukit\SchemaBundle\Command;

use Assetic\Util\VarUtils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;


use Pumukit\SchemaBundle\Document\Tag;

/**
 * Dumps assets as their source files are modified.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
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

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $stdout)
    {
        // capture error output
        $stderr = $stdout instanceof ConsoleOutputInterface
            ? $stdout->getErrorOutput()
            : $stdout;


	$this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
	$this->repo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Tag");

	
	$file = $input->getArgument('file');


	if (!file_exists($file)) {
	  $stderr->writeln("<error>Error stating " . $file . "</error>");
	  return -1;
	}


	$idCodMapping = array();

	$row = 1;
	if (($file = fopen($file, "r")) !== FALSE) {
	  while ( ($currentRow = fgetcsv($file, 300, ";")) !== FALSE)  {
            $number = count($currentRow);
            if ($number == 6 || $number == 8){
	      if (trim($currentRow[0]) == "id") { // header row 
		continue;
	      }

	      /*
	      if (!isset($imported_id_category_id[$currentRow[2]])){
		echo "\n\nCurrent csv row: " . $row . "\n";
		print_r($currentRow);
		throw new Exception ("Parent category not defined");
	      }

	      $parent_cat = CategoryPeer::retrieveByPk($imported_id_category_id[$currentRow[2]]);
	      $imported_id_category_id[$currentRow[0]] = $category->getId();
	      */

	      
	      $parentCat = isset($idCodMapping[$currentRow[2]])?$this->repo->findOneBy(array("cod" => $idCodMapping[$currentRow[2]])):null;
	      $category = $this->createCategoryFromCsvArray($currentRow, $parentCat);
	      $idCodMapping[$currentRow[0]] = $currentRow[1];

            } else {
	      $stderr->writeln("Last valid row = ...");
	      $stderr->writeln("Error: line $row has $number elements");
            }

            if ($row % 100 == 0 ) echo "Row " . $row . "\n";
            $previous_content = $currentRow;
            $row++;

	  } 
	  fclose($file);
	} else {
	  $stderr->writeln("<error>Error opening " . $file . "</error>");
	  return -1;
	}
    }


    /**
     *
     */
    private function createCategoryFromCsvArray($csv_array, $cat_parent=null)
    {
      $c = new Tag();
      if (!$tag = $this->repo->findOneBy(array("cod" => $csv_array[1]))) {
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
	$this->dm->flush();
	echo "Tag persisted - new id: " . $tag->getId() . " cod: " . $tag->getCod() . " title: " . $tag->getTitle() . "\n";
	
      } else {
        echo "\tNothing done - Tag retrieved from DB id: " . $tag->getId() . " cod: " . $tag->getCod() . " title: " . $tag->getTitle() . "\n";
        // ¿Update with csv_array?
        // ¿Check parent?
      }
      
      return $tag;
    }
}
