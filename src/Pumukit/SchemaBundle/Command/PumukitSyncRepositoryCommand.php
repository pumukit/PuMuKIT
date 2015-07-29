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
        $this->syncNumberMultimediaObjectsOnBroadcast($input, $output);
        $this->syncNumberMultimediaObjectsOnRoles($input, $output);
    }

    private function syncNumberMultimediaObjectsOnTags(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $tagRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Tag");
        $mmRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:MultimediaObject");

        $tags = $tagRepo->findAll();
        foreach ($tags as $tag) {
            $mms = $mmRepo->findWithTag($tag);
            if(count($mms) != 0){
                $output->writeln($tag->getCod().": ".$tag->getNumberMultimediaObjects()." -> ".count($mms));
            }
            $tag->setNumberMultimediaObjects(count($mms));
            $dm->persist($tag);
        }
        $dm->flush();
    }

    private function syncNumberMultimediaObjectsOnBroadcast(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $broadcastRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Broadcast");
        $mmRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:MultimediaObject");
    
        $output->writeln(" ");

        $broadcasts = $broadcastRepo->findAll();
        foreach ($broadcasts as $broadcast) {
            $mms = $mmRepo->findByBroadcast($broadcast);
            if(count($mms) != 0){
                $output->writeln($broadcast->getName().": ".$broadcast->getNumberMultimediaObjects()." -> ".count($mms));
            }
            $broadcast->setNumberMultimediaObjects(count($mms));
            $dm->persist($broadcast);
        }
        $dm->flush(); 
    }

    private function syncNumberMultimediaObjectsOnRoles(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $rolesRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Role");
        $mmRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:MultimediaObject");
    
        $output->writeln(" ");

        $roles = $rolesRepo->findAll();
        foreach ($roles as $role) {
            $persons = $mmRepo->findPersonWithRoleCod($role);
            if(count($persons) != 0){
                $output->writeln($role->getName().": ".$role->getNumberPeopleInMultimediaObject()." -> ".count($persons));
            }
            $role->setNumberPeopleInMultimediaObject(count($persons));
            $dm->persist($role);
        }
    }
}
