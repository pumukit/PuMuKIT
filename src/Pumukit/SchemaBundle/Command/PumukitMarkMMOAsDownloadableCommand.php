<?php

namespace Pumukit\SchemaBundle\Command;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PumukitMarkMMOAsDownloadableCommand extends ContainerAwareCommand
{
    private $dm;

    protected function configure()
    {
        $this
            ->setName('pumukit:mark:multimedia:downloadable')
            ->setDescription('Mark multimedia object as downloadable via series title')
            ->addArgument('word', InputArgument::REQUIRED, 'Word that includes the title of the series that indicates that it is downloadable')
            ->addArgument('language', InputArgument::REQUIRED, 'Language in which you want to search for the title of the series')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(
                <<<'EOT'

            The <info>pumukit:mark:multimedia:downloadable</info> command marks multimedia object as downloadable via series title.

            <info>php app/console pumukit:mark:multimedia:downloadable 'RECURSO'</info>

EOT
            )
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $titleWord = $input->getArgument('word');
        $language = $input->getArgument('language');

        $mmOs = $this->getMMOOfSeries($titleWord, $language);

        if (!$mmOs) {
            $output->writeln(sprintf('No multimediaObjects belonging to series with the word %s.', $titleWord));
        }

        $tag = $this->getContainer()->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(Tag::class)->findOneByCod('PUDERESOURCE');

        if (!$tag) {
            $output->writeln(sprintf('No tag with code %s.', 'PUDERESOURCE'));
        }

        foreach ($mmOs as $mmO) {
            $this->getContainer()->get('pumukitschema.tag')->addTagToMultimediaObject($mmO, $tag->getId());
        }
    }

    private function getMMOOfSeries($titleWord, $language)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $series = $dm->getRepository(Series::class)->createQueryBuilder()
            ->text($titleWord)
            ->language($language)
            ->getQuery()
            ->execute()
        ;

        if (!$series) {
            return new \InvalidArgumentException(sprintf('No series that include the word %s', $titleWord));
        }

        $all = [];
        $mmRepo = $dm->getRepository(MultimediaObject::class);

        foreach ($series as $serie) {
            $mmOs = $mmRepo->findWithStatus($serie, [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_NEW, MultimediaObject::STATUS_HIDDEN]);
            foreach ($mmOs as $mmO) {
                $all[] = $mmO;
            }
        }

        return $all;
    }
}
