<?php

namespace Pumukit\ImportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Pumukit\SchemaBundle\Entity\Tag;
use Symfony\Component\DependencyInjection\SimpleXMLElement;

/**
 * Dumps pumukit user info.
 *
 * @author Ruben Gonzalez <rubenrua@teltek.es>
 * @author Andres Perez
 */

class ImportTagsCommand extends ContainerAwareCommand
{
    private $em;
    private $trepo;
    private $default_locale= "es";
    private $output;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
       ->setName('pumukit:import:tags')
      ->setDefinition(array(
            new InputArgument('xml-file-path', InputArgument::REQUIRED, 'Path to xml file')
            ))
      ->setDescription('Import tags from UvigoTV PuMuKit.')
      ->setHelp(<<<EOF
The <info>%command.name%</info> command imports the category information
(genres, groundtypes and grounds, places and precincts) from previous
versions of Pumukit.

A new tag tree will be created in order to use them in Pumukit V2.

<error>Warning:</error> the existing <info>tag</info> and <info>ext_translations</info> tables
from the production environment <error>will be deleted!</error>

		   <info>php %command.full_name% xml-file-path</info>
EOF
            );
    }

    /**
    * {@inheritdoc}
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em     = $this->getContainer()->get("doctrine.orm.entity_manager");
        $this->trepo  = $this->em->getRepository('Gedmo\Translatable\Entity\Translation');
        $this->output = $output;

        // Remember: this uses the production environment database ("symfony")
        // Uncomment to DELETE DATABASE TABLES
        // $this->em->createQuery("DELETE PumukitSchemaBundle:Tag t")->getResult();
        // $this->em->createQuery("DELETE Gedmo\Translatable\Entity\Translation et WHERE et.objectClass = 'Pumukit\SchemaBundle\Entity\Tag'")->getResult();

          $path = $input->getArgument("xml-file-path");

        if (!file_exists($path)) {
            throw new \Exception("File does not exist ".$path);
        }

        $xml     = simplexml_load_file($path); // Throws ErrorException if not a valid XML.
        $num_cat = count($xml->tag);
        $output->writeln("\n<error>Warning: the prod database will be erased and re-created from $path</error>\n");

        // Create root tag, publication channel metatag and pub. channel tags.
        $root_tag = $this->setSimpleTag( "Root tag",
         "Tag raíz desde el que descienden el resto de categorías");
        $this->em->flush();
        $pub_channels_metatag = $this->setSimpleTag( "publication channels",
         null, $root_tag, true);
        $this->em->flush();

        foreach ( array( "WebTV", "ARCA", "iTunesU" ) as $pub_channel_title) {
            $tag = $this->setSimpleTag( $pub_channel_title, null,
                $pub_channels_metatag );
        }

        // Uncomment to generate an indented output showing all the tags.
        // foreach ($xml->tag as $category) $this->printNode($category, 0);

        $output->writeln("\nCounting $num_cat high-level categories.\n");
        foreach ($xml->tag as $category) {
            $output->writeln("Processing $category->title");
            $this->setTag($category, $root_tag);

        }

        $output->writeln("\nFlushing DB. This will take a moment.");

        $this->em->flush();
    }

    /**
     * setTag: creates and populates tags with the current xml node and all its children.
     * It is meant to be called not with the xml root but the subsequent nodes.
     *
     * @param SimpleXMLElement $node
     * @param Tag              $parent_tag
     */
    private function setTag(\SimpleXMLElement $node, Tag $parent_tag = null)
    {
        $tag   = new Tag();
        $tag->setParent($parent_tag);
        $tag->setMetatag("true" == $node['metatag']);
        $tag->setCod($node->cod);

        if (count($node->title->attributes()) > 0) {
            foreach ($node->title as $tit) {
                if ($this->default_locale == $tit['locale']) {
                    $tag->setTitle($tit); //Different locales => default = es.
                } else {
                    $this->trepo->translate($tag, 'title', $tit['locale'], $tit);
                }
            }
        } else {
            $tag->setTitle($node->title); // No locales => title = default
        }

        if (isset($node->description)) {
            if (count($node->description->attributes()) > 0) {
                foreach ($node->description as $desc) {
                    if ($this->default_locale == $desc['locale']) {
                        $tag->setDescription($desc); //Different locales => default = es.
                    } else {
                        $this->trepo->translate($tag, 'description', $desc['locale'], $desc);
                    }
                }
            } else {
                $tag->setDescription($node->description); // No locales => description = default
            }
        }

        $this->em->persist($tag); // Need to persist before going recursive or else Gedmo will jump to the chepa.

        if (isset($node->tags)) {
            foreach ($node->tags->tag as $child_tag) {
                $this->setTag($child_tag, $tag);
            }
        }
    }

    private function setSimpleTag($title, $description = null, Tag $parent = null,
        $metatag = false)
    {
        if (null == $parent) {
            $level = 0;
        } else {
            $level = $parent->getLevel() + 1;
        }
        $tabs = str_repeat("\t",$level);
        $this->output->writeln( $tabs . "Setting tag: " . $title );
        $tag = new Tag();
        $tag->setTitle( $title );
        $tag->setDescription( $description );
        if ($parent != null) {
            $tag->setParent( $parent );
        }
        $tag->setMetatag($metatag );
        // cod=0 by default but blank is more consistent with the imported tags.
        $tag->setCod("");
        $this->em->persist($tag);

        return $tag;
    }

    /*
     * printNode: prints the current node and all its children,
     * showing an indented tree hierarchy.
     *
     * For testing purpose only - use redirection to file
     * php app/console pumukit:import:tags input.xml >output.txt
     *
     * It is meant to be called not with the xml root but the subsequent nodes.
     */
    private function printNode(\SimpleXMLElement $node, $ntabs)
    {
        $tabs = str_repeat("\t",$ntabs);

        echo($tabs . "Es metatag: ". $node['metatag']. "\n");
        if (count($node->title->attributes()) > 0) {
            foreach ($node->title as $tit) {
                echo($tabs . "locale: " . $tit['locale'] . " - Title:" . $tit . "\n");
            }
        } else {
            echo($tabs . "Título: ".$node->title. "\n");
        }

        echo ($node->cod == "" ) ? $tabs . "Cod está vacío\n" : ($tabs . "Cod: " . $node->cod . "\n");

        if (isset($node->description)) {
            if (count($node->description->attributes()) > 0) {
                foreach ($node->description as $desc) {
                    echo($tabs . "locale: " . $desc['locale'] . " - Description:" . $desc . "\n");
                }
            } else {
                echo($tabs . "Descripción: ".$node->description. "\n");
            }

        } else {
            echo $tabs . "Descripción está vacía\n";
        }

        if (isset($node->tags)) {
            echo ($tabs . "Contiene tags\n");
            foreach ($node->tags->tag as $child_tag) {
                $this->printNode($child_tag, $ntabs + 1);
            }
        } else {
            echo ($tabs . "Este tag es final\n");
        }

        echo $tabs . "------------------------------\n";
    }

    // TO DO: create a function that prints the xml tag tree from the database
    // and compare it to printNode output.
}
