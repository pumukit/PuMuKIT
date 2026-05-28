<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\TextIndexService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RebuildTextIndexCommand extends Command
{
    private const BATCH_SIZE = 100;

    private DocumentManager $documentManager;
    private TextIndexService $textIndexService;

    public function __construct(DocumentManager $documentManager, TextIndexService $textIndexService)
    {
        $this->documentManager = $documentManager;
        $this->textIndexService = $textIndexService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:textindex:rebuild')
            ->setDescription('Rebuild the MongoDB text index field for all Multimedia Objects and Series')
            ->setHelp(
                <<<'EOT'
            Regenerates the denormalized "textindex" field used by the text search
            (the $text MongoDB operator) for every Multimedia Object and Series.

            Run it after importing/migrating data, or whenever the search returns no
            results for content that was never saved through the admin edit form
            (the field is only populated on update events, not on creation).

                Example:
                    php bin/console pumukit:textindex:rebuild

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->rebuild(
            $output,
            'Multimedia Objects',
            MultimediaObject::class,
            fn (MultimediaObject $mmObj) => $this->textIndexService->updateMultimediaObjectTextIndex($mmObj)
        );

        $this->rebuild(
            $output,
            'Series',
            Series::class,
            fn (Series $series) => $this->textIndexService->updateSeriesTextIndex($series)
        );

        $output->writeln('');
        $output->writeln('<info>TextIndex rebuilt for Multimedia Objects and Series.</info>');

        return Command::SUCCESS;
    }

    private function rebuild(OutputInterface $output, string $label, string $documentClass, callable $updater): void
    {
        $repository = $this->documentManager->getRepository($documentClass);

        $total = (int) $repository->createQueryBuilder()->count()->getQuery()->execute();
        $documents = $repository->createQueryBuilder()->getQuery()->execute();

        $output->writeln('');
        $output->writeln(sprintf('Updating %s...', $label));

        $progress = new ProgressBar($output, $total);
        $progress->setFormat('verbose');
        $progress->start();

        $processed = 0;
        foreach ($documents as $document) {
            $updater($document);
            $progress->advance();

            if (0 === ++$processed % self::BATCH_SIZE) {
                $this->documentManager->flush();
                $this->documentManager->clear();
            }
        }

        $this->documentManager->flush();
        $this->documentManager->clear();
        $progress->finish();
        $output->writeln('');
    }
}
