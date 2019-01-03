<?php

namespace Pumukit\ArcaBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Tag;

/**
 * Class ArcaInitTagsCommand.
 */
class ArcaInitTagsCommand extends ContainerAwareCommand
{
    private $dm = null;
    private $tagRepo = null;

    protected function configure()
    {
        $this
            ->setName('arca:init:tags')
            ->setDescription('Load arca tag data fixture to your database')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<'EOT'
Command to load a controlled Arca tags data into a database. Useful for init Arca environment.

The --force parameter has to be used to actually drop the database.

EOT
          );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            $arcaPublicationChannelTag = $this->createTagWithCode('PUCHARCA', 'ARCA', 'PUBCHANNELS', false);
            $this->dm->persist($arcaPublicationChannelTag);
            $this->dm->flush();

            $output->writeln('Tag persisted - new id: '.$arcaPublicationChannelTag->getId().' cod: '.$arcaPublicationChannelTag->getCod());
        } else {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment without backup.');
            $output->writeln('');
            $output->writeln('Please run the operation with --force to execute.');

            return -1;
        }

        return 0;
    }

    /**
     * @param string $code
     * @param string $title
     * @param string $tagParentCode
     * @param bool   $metatag
     *
     * @return Tag
     *
     * @throws \Exception
     */
    private function createTagWithCode($code, $title, $tagParentCode = null, $metatag = false)
    {
        if ($tag = $this->findTag($code)) {
            throw new \Exception('Nothing done - Tag retrieved from DB id: '.$tag->getId().' cod: '.$tag->getCod());
        }

        $tag = new Tag();
        $tag->setCod($code);
        $tag->setMetatag($metatag);
        $tag->setDisplay(true);
        $tag->setTitle($title, 'es');
        $tag->setTitle($title, 'gl');
        $tag->setTitle($title, 'en');
        if ($tagParentCode) {
            if ($parent = $this->findTag($tagParentCode)) {
                $tag->setParent($parent);
            } else {
                throw new \Exception('Nothing done - There is no tag in the database with code '.$tagParentCode.' to be the parent tag');
            }
        }

        $this->dm->persist($tag);
        $this->dm->flush();

        return $tag;
    }

    /**
     * @param string $cod
     *
     * @return mixed
     */
    private function findTag($cod)
    {
        return $this->dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod($cod);
    }
}
