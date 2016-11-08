<?php

namespace Pumukit\StatsBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Role;

class PumukitCleanLogCommand extends ContainerAwareCommand
{
    private $dm = null;

    protected function configure()
    {
        $this
            ->setName('pumukit:stats:clean')
            ->setDescription('Clean bots from ViewsLog collections')
            ->setHelp(<<<'EOT'
Clean bots, crawlers, spiders and validators from ViewsLog collections.

This command doesn't work in `prod` environments.

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $detector = $this->getContainer()->get('vipx_bot_detect.detector');

        //TODO add to pumukit yml.
        $this->execRemoveQuery('TTK Zabbix Agent');

        foreach ($detector->getMetadatas() as $metadata) {
            if ('' === $metadata->getAgent() && 'exact' !== $metadata->getAgentMatch()) {
                continue;
            }

            if ('exact' === $metadata->getAgentMatch()) {
                $this->execRemoveQuery($metadata->getAgent());
            } else {
                $regex = sprintf('/%s/', preg_quote($metadata->getAgent()));
                $this->execRemoveQuery(new \MongoRegex($regex));
            }
        }

        $output->writeln('Done');
    }

    private function execRemoveQuery($userAgent)
    {
        $qb = $this->dm->createQueryBuilder('PumukitStatsBundle:ViewsLog')
          ->remove()
          ->multiple(true)
          ->field('userAgent')->equals($userAgent)
          ->getQuery()
          ->execute();
    }
}
