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
        $this->syncNumberPeopleInMultimediaObjectsOnRoles($input, $output);
    }

    private function syncNumberMultimediaObjectsOnTags(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $tagRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Tag");
        $mmRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:MultimediaObject");

        $tags = $tagRepo->findAll();
        foreach ($tags as $tag) {
            $mms = $mmRepo->findWithTag($tag);
            $output->writeln($tag->getCod().": ".count($mms));
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
            $output->writeln($broadcast->getName().": ".count($mms));
            $broadcast->setNumberMultimediaObjects(count($mms));
            $dm->persist($broadcast);
        }
        $dm->flush(); 
    }

    private function syncNumberPeopleInMultimediaObjectsOnRoles(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $rolesRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Role");
        $mmRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:MultimediaObject");
    
        $output->writeln(" ");

        $roles = $rolesRepo->findAll();
        foreach ($roles as $role) {
            $people = $mmRepo->countPeopleWithRoleCode($role->getCod());
            $output->writeln($role->getName().": ".count($people));
            $role->setNumberPeopleInMultimediaObject(count($people));
            $dm->persist($role);
        }
        $dm->flush();
    }
}
