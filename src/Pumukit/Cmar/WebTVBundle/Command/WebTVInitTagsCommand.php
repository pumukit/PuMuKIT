<?php

namespace Pumukit\Cmar\WebTVBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Tag;

class WebTVInitTagsCommand extends ContainerAwareCommand
{
    private $dm = null;
    private $tagsRepo = null;

    private $tagsPath = "../Resources/data/tags/";

    protected function configure()
    {
        $this
            ->setName('webtv:init:tags')
            ->setDescription('Load Pumukit data fixtures to your database')
            ->addArgument('file', InputArgument::OPTIONAL, 'Input CSV path')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<EOT

Command to load a controlled set of data into a database. Useful for init Pumukit WebTV environment.

The --force parameter has to be used to actually modify the database.

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        if ($input->getOption('force')) {
            $errorExecuting = $this->executeTags($input, $output);
            if (-1 === $errorExecuting) return -1;
        } else {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
            $output->writeln('');
            $output->writeln('<info>Would drop the database</info>');
            $output->writeln('Please run the operation with --force to execute.');
            $output->writeln('<error>All data will be lost!</error>');

            return -1;
        }
    }

    protected function executeTags(InputInterface $input, OutputInterface $output)
    {
        $this->tagsRepo = $this->dm->getRepository("PumukitSchemaBundle:Tag");

        $finder = new Finder();
        $finder->files()->in(__DIR__.'/'.$this->tagsPath);
        $file = $input->getArgument('file');
        if ((0 == strcmp($file, "")) && (!$finder)) {
            $output->writeln("<error>Tags: There's no data to initialize</error>");
        
            return -1;
        }
        $publishingDecisionTag = $this->tagsRepo->findOneByCod("PUBDECISIONS");
        if (null == $publishingDecisionTag) {
            throw new \Exception("Trying to add children tags to the not created Publishing Decision Tag. Please init pumukit tags");
        }
        foreach ($finder as $tagFile) {
            $this->createFromFile($tagFile, $publishingDecisionTag, $output);
        }
        if ($file) {
            $this->createFromFile($file, $publisingDecisionTag, $output);
        }

        return 0;
    }

    protected function createFromFile($file, $parentTag, OutputInterface $output)
    {
        if (!file_exists($file)) {
            $output->writeln("<error>".$repoName.": Error stating ".$file."</error>");

            return -1;
        }

        $idCodMapping = array();

        $row = 1;
        if (($file = fopen($file, "r")) !== false) {
            while (($currentRow = fgetcsv($file, 300, ";")) !== false) {
                $number = count($currentRow);
                if ($number == 6 || $number == 8){
                    //Check header rows
                    if (trim($currentRow[0]) == "id") {
                        continue;
                    }

                    $parent = isset($idCodMapping[$currentRow[2]])
                      ? $idCodMapping[$currentRow[2]]
                      : $parentTag;

                    try {
                        $tag = $this->createTagFromCsvArray($currentRow, $parent);
                        $idCodMapping[$currentRow[0]] = $tag;
                        $output->writeln("Tag persisted - new id: ".$tag->getId()." cod: ".$tag->getCod());
                    } catch (Exception $e) {
                        $output->writeln("<error>Tag: ".$e->getMessage()."</error>");
                    }
                } else {
                    $output->writeln($repoName.": Last valid row = ...");
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
        if ($tag = $this->tagsRepo->findOneByCod($csv_array[1])) {
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
