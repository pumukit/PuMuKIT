<?php

namespace Pumukit\SchemaBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Tag;

class PumukitSyncRepositoryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:sync:repository')
            ->setDescription('Sync denormalized repository')
            ->setHelp(<<<EOT
Denormalize the database is necessary to increase the performance of the app. This command syncs denormalized repository, for instance:

 * Sync number of multimedia object in tags (tags.number_multimedia_objects).

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->syncNumberMultimediaObjectsOnTags($input, $output);
    }

    private function syncNumberMultimediaObjectsOnTags(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $tagRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Tag");
        $mmRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:MultimediaObject");

        $tags = $tagRepo->findAll();
        foreach ($tags as $tag) {
            $mms = $mmRepo->findBy(array("tags.cod" => $tag->getCod()));
            $output->writeln($tag->getCod().": ".$tag->getNumberMultimediaObjects()." -> ".count($mms));
            $tag->setNumberMultimediaObjects(count($mms));
            $dm->persist($tag);
        }
        $dm->flush();
    }
}
