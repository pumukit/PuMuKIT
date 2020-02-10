<?php

namespace Pumukit\StatsBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use Pumukit\StatsBundle\Document\ViewsLog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vipx\BotDetect\BotDetector;

class PumukitCleanLogCommand extends Command
{
    /** @var DocumentManager */
    private $documentManager;
    /** @var BotDetector */
    private $vipxBotDetectorService;
    private $from;

    public function __construct(DocumentManager $documentManager, BotDetector $vipxBotDetectorService)
    {
        $this->documentManager = $documentManager;
        $this->vipxBotDetectorService = $vipxBotDetectorService;
        parent::__construct();
    }

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
        $from = $input->getOption('from');
        if ($from) {
            $this->from = new \DateTimeImmutable($from);
        }

        $this->execRemoveQuery('TTK Zabbix Agent');

        foreach ($this->vipxBotDetectorService->getMetadatas() as $metadata) {
            if ('' === $metadata->getAgent() && 'exact' !== $metadata->getAgentMatch()) {
                continue;
            }

            if ('exact' === $metadata->getAgentMatch()) {
                $this->execRemoveQuery($metadata->getAgent());
            } else {
                $regex = sprintf('/%s/', preg_quote($metadata->getAgent()));
                $this->execRemoveQuery(new Regex($regex));
            }
        }

        $output->writeln('Done');
    }

    private function execRemoveQuery($userAgent): void
    {
        $qb = $this->documentManager->createQueryBuilder(ViewsLog::class)
            ->remove()
            ->multiple(true)
            ->field('userAgent')->equals($userAgent);

        if ($this->from) {
            $qb->field('date')->gte($this->from);
        }

        $qb->getQuery()->execute();
    }
}
