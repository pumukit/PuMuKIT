<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Utils\FinderUtils;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CheckStorageConsistencyCommand extends Command
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

        if ($input->getOption('tracks')) {
            $storageData = $this->checkMediaStorage('tracks');

            $this->printResults($this->storageResultHeaders, 'TRACKS', $storageData);
        }

        if ($input->getOption('documents')) {
            $storageData = $this->checkMediaStorage('documents');

            $this->printResults($this->storageResultHeaders, 'DOCUMENTS', $storageData);
        }

        if ($input->getOption('images')) {
            $storageData = $this->checkMediaStorage('images');

            $this->printResults($this->storageResultHeaders, 'IMAGES', $storageData);
        }

        if ($input->getOption('materials')) {
            $storageData = $this->checkElementStorage('materials');

            $this->printResults($this->storageResultHeaders, 'MATERIALS', $storageData);
        }

        if ($input->getOption('pics')) {
            $storageData = $this->checkElementStorage('pics');

            $this->printResults($this->storageResultHeaders, 'PICS', $storageData);
        }

        return Command::SUCCESS;
    }

    public function checkMediaStorage(string $mediaField): array
    {
        $storageResult = [];
        $allMultimediaObjects = $this->getAllMultimediaObjectsWith($mediaField);
        $progressBar = new ProgressBar($this->output, count($allMultimediaObjects));

        $progressBar->start();

        foreach ($allMultimediaObjects as $multimediaObject) {
            foreach ($multimediaObject[$mediaField] as $media) {
                $path = $media['storage']['path'] ?? null;
                if (null !== $path && !FinderUtils::isValidFile($path)) {
                    $storageResult[] = [(string) $multimediaObject['_id'], $path];
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return $storageResult;
    }

    public function checkElementStorage(string $elementField): array
    {
        $storageResult = [];
        $allMultimediaObjects = $this->getAllMultimediaObjectsWith($elementField);
        $progressBar = new ProgressBar($this->output, count($allMultimediaObjects));

        $progressBar->start();

        foreach ($allMultimediaObjects as $multimediaObject) {
            foreach ($multimediaObject[$elementField] as $element) {
                $path = $element['path'] ?? null;
                if (null !== $path && !FinderUtils::isValidFile($path)) {
                    $storageResult[] = [(string) $multimediaObject['_id'], $path];
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

    public function getAllMultimediaObjectsWith(string $field)
    {
        $qb = $this->documentManager->createQueryBuilder(MultimediaObject::class);
        $qb->field($field)->exists(true);
        $qb->hydrate(false);

        return $qb->getQuery()->execute();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:check:storage')
            ->setDescription('Check if tracks, documents, images, materials and pics defined in DB exist physically in their storage')
            ->addOption('tracks', null, InputOption::VALUE_NONE, 'Use this to check all tracks storage.')
            ->addOption('documents', null, InputOption::VALUE_NONE, 'Use this to check all documents storage.')
            ->addOption('images', null, InputOption::VALUE_NONE, 'Use this to check all images storage.')
            ->addOption('materials', null, InputOption::VALUE_NONE, 'Use this to check all materials storage.')
            ->addOption('pics', null, InputOption::VALUE_NONE, 'Use this to check all pics storage.')
            ->setHelp(
                <<<'EOT'
The <info>pumukit:check:storage</info> command checks the storage paths saved on DB.

    Options:
        Tracks — Check all tracks storage to find removed or unknown paths saved on DB.

        <info>php bin/console pumukit:check:storage --tracks</info>


        Documents — Check all documents storage to find removed or unknown paths saved on DB.

        <info>php bin/console pumukit:check:storage --documents</info>


        Images — Check all images storage to find removed or unknown paths saved on DB.

        <info>php bin/console pumukit:check:storage --images</info>


        Materials — Check all materials storage to find removed or unknown paths saved on DB.

        <info>php bin/console pumukit:check:storage --materials</info>


        Pics — Check all pics storage to find removed or unknown paths saved on DB.

        <info>php bin/console pumukit:check:storage --pics</info>
EOT
            )
        ;
    }
}
