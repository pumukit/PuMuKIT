<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Services\TrackDeliveryService;
use Pumukit\SchemaBundle\Document\Exception\PathException;
use Pumukit\SchemaBundle\Document\MediaType\Media;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'pumukit:check:track-delivery', description: 'List the local tracks the download server cannot serve')]
final class CheckTrackDeliveryCommand extends Command
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly TrackDeliveryService $trackDeliveryService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of undeliverable tracks to list', '20');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(0, (int) $input->getOption('limit'));

        $cursor = $this->documentManager->createQueryBuilder(MultimediaObject::class)
            ->field('tracks')->exists(true)
            ->getQuery()
            ->execute()
        ;

        $checked = 0;
        $undeliverable = 0;
        $processed = 0;
        foreach ($cursor as $multimediaObject) {
            foreach ($multimediaObject->getTracks() as $track) {
                ++$checked;
                $reason = $this->trackDeliveryService->undeliverableReason($track);
                if (null === $reason) {
                    continue;
                }

                ++$undeliverable;
                if ($undeliverable <= $limit) {
                    $output->writeln(sprintf('%s %s %s %s', $multimediaObject->getId(), $track->id(), $this->path($track), $reason));
                }
            }

            if (0 === ++$processed % self::BATCH_SIZE) {
                $this->documentManager->clear();
            }
        }

        $summary = sprintf('Checked %d tracks, %d cannot be served by the download server.', $checked, $undeliverable);
        if ($undeliverable > $limit) {
            $summary .= sprintf(' Only the first %d are listed.', $limit);
        }

        if ($undeliverable > 0) {
            $io->error($summary);

            return Command::FAILURE;
        }

        $io->success($summary);

        return Command::SUCCESS;
    }

    private function path(Media $track): string
    {
        try {
            return $track->storage()->path()?->path() ?? '';
        } catch (PathException) {
            return (string) $track->getUrl();
        }
    }
}
