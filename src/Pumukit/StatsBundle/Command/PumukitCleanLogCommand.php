<?php

namespace Pumukit\StatsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PumukitCleanLogCommand extends ContainerAwareCommand
{
    private $dm;
    private $from;

    protected function configure()
    {
        $this
            ->setName('pumukit:stats:clean')
            ->setDescription('Clean bots from ViewsLog collections')
            ->addOption('from', null, InputOption::VALUE_OPTIONAL, 'Define period to clean the stats. Use a PHP Date Format')
            ->setHelp(
                <<<'EOT'
Clean bots, crawlers, spiders and validators from ViewsLog collections.

Examples:
<info>php app/console pumukit:stats:clean</info>
<info>php app/console pumukit:stats:clean --from yesterday</info>
<info>php app/console pumukit:stats:clean --from 'monday this week - 1 week'</info>

EOT
          )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $detector = $this->getContainer()->get('vipx_bot_detect.detector');

        $from = $input->getOption('from');
        if ($from) {
            $this->from = new \DateTimeImmutable($from);
        }

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
            ->field('userAgent')->equals($userAgent);

        if ($this->from) {
            $qb->field('date')->gte($this->from);
        }

        $qb->getQuery()
            ->execute()
        ;
    }
}
