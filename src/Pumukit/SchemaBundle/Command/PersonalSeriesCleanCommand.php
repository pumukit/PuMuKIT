<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\PersonalSeriesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'pumukit:personal-series:clean', description: 'Delete the personal series that have no multimedia objects')]
final class PersonalSeriesCleanCommand extends Command
{
    private const BATCH = 100;

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly FactoryService $factoryService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Delete the empty personal series. Without it the command only counts them')
            ->addOption('older-than', null, InputOption::VALUE_REQUIRED, 'Only the series created at least this number of days ago, so that the uploads in progress keep their series', '1')
            ->setHelp(
                <<<'EOT'
Personal series are created the first time a user uploads or records a video. Before 6.0 they
were created on every login, so many of them are empty. This command finds the personal series
that only have the template and, with --force, deletes them and unlinks them from their users.
A user whose series is deleted gets a new one the next time they upload.

A personal series is created when the upload starts and its first multimedia object when the file
has been uploaded, so the series created less than --older-than days ago (1 by default) are kept.

<info>php bin/console pumukit:personal-series:clean</info>
<info>php bin/console pumukit:personal-series:clean --force</info>
<info>php bin/console pumukit:personal-series:clean --force --older-than=30</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');
        $days = (string) $input->getOption('older-than');
        if (!ctype_digit($days)) {
            $io->error('--older-than must be a number of days.');

            return Command::INVALID;
        }

        $ids = $this->emptyPersonalSeriesIds(time() - (int) $days * 86400);
        if ([] === $ids) {
            $io->success('There are no empty personal series.');

            return Command::SUCCESS;
        }

        if (!$force) {
            $io->note(sprintf('%d empty personal series found. Run the command with --force to delete them.', count($ids)));

            return Command::SUCCESS;
        }

        $deleted = 0;
        $progress = $io->createProgressBar(count($ids));
        foreach (array_chunk($ids, self::BATCH) as $chunk) {
            $deletedIds = [];
            foreach ($chunk as $id) {
                $series = $this->documentManager->getRepository(Series::class)->find($id);
                if ($series instanceof Series && 0 === $this->multimediaObjects()->countWithoutPrototype($series)) {
                    $this->factoryService->deleteSeries($series);
                    $deletedIds[] = $id;
                }
                $progress->advance();
            }
            if ([] !== $deletedIds) {
                $this->documentManager->getDocumentCollection(User::class)->updateMany(
                    ['personalSeries' => ['$in' => $deletedIds]],
                    ['$unset' => ['personalSeries' => true]]
                );
                $deleted += count($deletedIds);
            }
            $this->documentManager->clear();
        }
        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf('%d empty personal series deleted.', $deleted));

        return Command::SUCCESS;
    }

    private function emptyPersonalSeriesIds(int $createdBefore): array
    {
        $ids = [];
        $cursor = $this->documentManager->getDocumentCollection(Series::class)->find(
            [
                'properties.'.PersonalSeriesService::DEFAULT_PERSONAL_SERIES_PROPERTY => true,
                '_id' => ['$lte' => new ObjectId(sprintf('%08x', $createdBefore).'ffffffffffffffff')],
            ],
            ['projection' => ['_id' => 1]]
        );

        foreach ($cursor as $document) {
            $count = $this->documentManager->getDocumentCollection(MultimediaObject::class)->countDocuments([
                'series' => $document['_id'],
                'status' => ['$ne' => MultimediaObject::STATUS_PROTOTYPE],
            ]);
            if (0 === $count) {
                $ids[] = (string) $document['_id'];
            }
        }

        return $ids;
    }

    private function multimediaObjects()
    {
        return $this->documentManager->getRepository(MultimediaObject::class);
    }
}
