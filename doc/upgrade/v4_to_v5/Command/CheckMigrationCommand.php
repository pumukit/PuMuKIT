<?php

declare(strict_types=1);

namespace Upgrade\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Utils\FinderUtils;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CheckMigrationCommand extends Command
{
    protected DocumentManager $documentManager;

    protected array $storageResultHeaders = ['Multimedia Object', 'Wrong path'];
    protected OutputInterface $output;

    public function __construct(DocumentManager $documentManager)
    {
        parent::__construct();
        $this->documentManager = $documentManager;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        if ($input->getOption('storage')) {
            $storageData = $this->processAndCheckTrackStorage();

            $this->printResults($this->storageResultHeaders, 'TRACKS', $storageData);
        }

        if ($input->getOption('materials')) {
            $storageData = $this->processAndCheckMaterialStorage();

            $this->printResults($this->storageResultHeaders, 'MATERIALS', $storageData);
        }

        if ($input->getOption('pics')) {
            $storageData = $this->processAndCheckPicStorage();

            $this->printResults($this->storageResultHeaders, 'PICS', $storageData);
        }

        return Command::SUCCESS;
    }

    public function processAndCheckTrackStorage(): array
    {
        $storageResult = [];
        $allMultimediaObjects = $this->getAllMultimediaObjectsWithTracks();
        $progressBar = new ProgressBar($this->output, count($allMultimediaObjects));

        $progressBar->start();

        foreach ($allMultimediaObjects as $multimediaObject) {
            $tracks = $multimediaObject['tracks'];
            foreach ($tracks as $track) {
                if (null !== $track['path'] && !FinderUtils::isValidFile($track['path'])) {
                    $storageResult[] = [$multimediaObject['_id'], $track['path']];
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return $storageResult;
    }

    public function processAndCheckMaterialStorage(): array
    {
        $storageResult = [];
        $allMultimediaObjects = $this->getAllMultimediaObjectsWithMaterials();
        $progressBar = new ProgressBar($this->output, count($allMultimediaObjects));

        $progressBar->start();

        foreach ($allMultimediaObjects as $multimediaObject) {
            $materials = $multimediaObject['materials'];
            foreach ($materials as $element) {
                if (null !== $element['path'] && !FinderUtils::isValidFile($element['path'])) {
                    $storageResult[] = [$multimediaObject['_id'], $element['path']];
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return $storageResult;
    }

    public function processAndCheckPicStorage(): array
    {
        $storageResult = [];
        $allMultimediaObjects = $this->getAllMultimediaObjectsWithPics();
        $progressBar = new ProgressBar($this->output, count($allMultimediaObjects));

        $progressBar->start();

        foreach ($allMultimediaObjects as $multimediaObject) {
            $pics = $multimediaObject['pics'];
            foreach ($pics as $element) {
                if (null !== $element['path'] && !FinderUtils::isValidFile($element['path'])) {
                    $storageResult[] = [$multimediaObject['_id'], $element['path']];
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return $storageResult;
    }

    public function printResults(array $headers, string $type, array $data): void
    {
        $this->output->writeln('');
        $this->output->writeln('***** '.$type.' RESULTS *****');
        $table = new Table($this->output);
        $table->setHeaders($headers);
        $table->setRows($data);
        $table->render();
    }

    public function getAllMultimediaObjectsWithTracks()
    {
        $qb = $this->documentManager->createQueryBuilder(MultimediaObject::class);
        $qb->field('tracks')->exists(true);
        $qb->hydrate(false);

        return $qb->getQuery()->execute();
    }

    public function getAllMultimediaObjectsWithMaterials()
    {
        $qb = $this->documentManager->createQueryBuilder(MultimediaObject::class);
        $qb->field('materials')->exists(true);
        $qb->hydrate(false);

        return $qb->getQuery()->execute();
    }

    public function getAllMultimediaObjectsWithPics()
    {
        $qb = $this->documentManager->createQueryBuilder(MultimediaObject::class);
        $qb->field('pics')->exists(true);
        $qb->hydrate(false);

        return $qb->getQuery()->execute();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:upgrade:check')
            ->setDescription('Check DB before execute schema from v4 to v5')
            ->addOption('storage', null, InputOption::VALUE_NONE, 'Use this to check all tracks storage.')
            ->addOption('materials', null, InputOption::VALUE_NONE, 'Use this to check all materials storage.')
            ->addOption('pics', null, InputOption::VALUE_NONE, 'Use this to check all pics storage.')
            ->setHelp(
                <<<'EOT'
The <info>pumukit:upgrade:check</info> check system using filters before execute schema migration.

    Options:
        Storage — Check all tracks storage to find removed or unknown paths saved on DB.

        <info>php bin/console pumukit:upgrade:check --storage</info>


        Materials — Check all materials storage to find removed or unknown paths saved on DB.

        <info>php bin/console pumukit:upgrade:check --materials</info>


        Pics — Check all pics storage to find removed or unknown paths saved on DB.

        <info>php bin/console pumukit:upgrade:check --pics</info>
EOT
            )
        ;
    }
}
