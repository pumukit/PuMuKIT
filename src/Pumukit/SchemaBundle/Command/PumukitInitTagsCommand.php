<?php

namespace Pumukit\SchemaBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Tag;

class PumukitInitTagsCommand extends ContainerAwareCommand
{
    private $dm = null;
    private $repo = null;

    private $tagsPath = "../Resources/data/tags/";

    protected function configure()
    {
        $this
            ->setName('pumukit:init:tags')
            ->setDescription('Load Pumukit data fixtures to your database')
            ->addArgument('file', InputArgument::OPTIONAL, 'Input CSV path')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<EOT

Command to load a controlled set of data into a database. Useful for init Pumukit environment.

The --force parameter has to be used to actually drop the database.

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Tag");

        if ($input->getOption('force')) {
            $finder = new Finder();
            $finder->files()->in(__DIR__.'/'.$this->tagsPath);
            $file = $input->getArgument('file');
            if ((0 == strcmp($file, "")) && (!$finder)) {
                $output->writeln("<error>There's no data to initialize</error>");

                return -1;
            }
            $this->removeTags();
            $root = $this->createRoot();
            foreach ($finder as $tagFile) {
                $this->createFromFile($tagFile, $root, $output);
            }
            if ($file) {
                $this->createFromFile($file, $root, $output);
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

    protected function removeTags()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')->remove(array());
    }

    protected function createRoot()
    {
        $root = $this->createTagFromCsvArray(array(null, "ROOT", 1, 0, "ROOT", "ROOT", "ROOT"));
        $this->dm->flush();

        return $root;
    }

    protected function createFromFile($file, $root, OutputInterface $output)
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
                if ($number == 6 || $number == 8) {
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
                        $output->writeln("Tag persisted - new id: ".$tag->getId()." cod: ".$tag->getCod());
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
     *
     */
    private function createTagFromCsvArray($csv_array, $tag_parent = null)
    {
        $c = new Tag();
        if ($tag = $this->repo->findOneByCod($csv_array[1])) {
            throw new \LengthException("Nothing done - Tag retrieved from DB id: ".$tag->getId()." cod: ".$tag->getCod());
        }

        $tag = new Tag();
        $tag->setCod($csv_array[1]);
        $tag->setMetatag($csv_array[3]);
        $tag->setDisplay($csv_array[4]);
        if ($tag_parent) {
            $tag->setParent($tag_parent);
        }
        $tag->setTitle($csv_array[5], 'es');
      // NOTE Take care of csv language order!
      if (isset($csv_array[6])) {
          $tag->setTitle($csv_array[6], 'gl');
      }
        if (isset($csv_array[7])) {
            $tag->setTitle($csv_array[7], 'en');
        }

        $this->dm->persist($tag);

        return $tag;
    }
}
