<?php

namespace Pumukit\SchemaBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Tag;

class TagCommand extends ContainerAwareCommand
{
    private $dm;
    private $tagRepo;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('pumukit:tag:update')
            ->setDescription('Update the tags fields')
            ->setDefinition([
                new InputArgument('tag', InputArgument::REQUIRED, 'The tag'),
                new InputOption('display', null, InputOption::VALUE_NONE, 'Use to set the display field of a tag to true, set to false by default'),
            ])
            ->setHelp(<<<'EOT'
The <info>pumukit:tag:update</info> command set the display field of a tag to true/false.

  <info>php app/console pumukit:tag:update PUDEPD1</info>
  <info>php app/console pumukit:tag:update --display PUDENEW</info>
EOT
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initParameters();

        $tagCode = $input->getArgument('tag');
        $tag = $this->getTag($tagCode);

        $display = (true === $input->getOption('display'));

        $tag = $this->updateTag($tag, $display);

        $output->writeln(sprintf('<info>Tag with code "%s" has been set with display to %b.</info>', $tagCode, $display));
    }

    private function initParameters()
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->tagRepo = $this->dm->getRepository(Tag::class);
    }

    private function getTag($tagCode)
    {
        $tag = $this->tagRepo->findOneByCod($tagCode);
        if (!$tag) {
            throw new \InvalidArgumentException(sprintf('No tag with code %s', $tagCode));
        }

        return $tag;
    }

    private function updateTag($tag, $display)
    {
        $tag->setDisplay($display);
        $this->dm->persist($tag);
        $this->dm->flush();

        return $tag;
    }
}
