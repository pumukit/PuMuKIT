<?php

namespace Pumukit\SchemaBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TagCommand extends Command
{
    private $dm;
    private $tagRepo;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->tagRepo = $this->dm->getRepository(Tag::class);

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('pumukit:tag:update')
            ->setDescription('Update the tags fields')
            ->setDefinition([
                new InputArgument('tag', InputArgument::REQUIRED, 'The tag'),
                new InputOption('display', null, InputOption::VALUE_NONE, 'Use to set the display field of a tag to true, set to false by default'),
            ])
            ->setHelp(
                <<<'EOT'
The <info>pumukit:tag:update</info> command set the display field of a tag to true/false.

  <info>php app/console pumukit:tag:update PUDEPD1</info>
  <info>php app/console pumukit:tag:update --display PUDENEW</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tagCode = $input->getArgument('tag');
        $tag = $this->tagRepo->findOneByCod($tagCode);

        if (!$tag) {
            throw new \InvalidArgumentException(sprintf('No tag with code %s', $tagCode));
        }

        $display = (true === $input->getOption('display'));

        $this->updateTag($tag, $display);

        $output->writeln(sprintf('<info>Tag with code "%s" has been set with display to %b.</info>', $tagCode, $display));

        return 0;
    }

    private function updateTag(Tag $tag, bool $display): Tag
    {
        $tag->setDisplay($display);
        $this->dm->persist($tag);
        $this->dm->flush();

        return $tag;
    }
}
