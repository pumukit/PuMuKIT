<?php

declare(strict_types=1);

namespace Upgrade\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class UpgradePathsCommand extends Command
{
    protected DocumentManager $documentManager;

    protected array $storageResultHeaders = ['Multimedia Object', 'Base path', 'New path'];
    protected OutputInterface $output;
    private string $findPath;
    private string $replacePath;
    private Regex $findPathRegex;
    private bool $force;

    public function __construct(DocumentManager $documentManager)
    {
        parent::__construct();
        $this->documentManager = $documentManager;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->findPath = $input->getOption('find');
        $this->replacePath = $input->getOption('replace');
        $this->findPathRegex = new Regex($this->findPath, 'i');
        $this->force = $input->getOption('force');

        if ($input->getOption('storage')) {
            $storageData = $this->processAndCheckTrackStorage();
            $this->printResults($this->storageResultHeaders, 'TRACKS', $storageData);

            $jobsData = $this->processAndCheckJobStorage();
            $this->printResults($this->storageResultHeaders, 'JOBS', $jobsData);
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
                if (null !== $track['path'] && str_contains($track['path'], $this->findPath)) {
                    $replacePath = str_replace($this->findPath, $this->replacePath, $track['path']);
                    $storageResult[] = [$multimediaObject['_id'], $track['path'], $replacePath];
                    if ($this->force) {
                        $this->documentManager->createQueryBuilder(MultimediaObject::class)
                            ->updateOne()
                            ->field('tracks._id')->equals($track['_id'])
                            ->field('tracks.$.path')->set($replacePath)
                            ->getQuery()
                            ->execute()
                        ;
                        $this->documentManager->flush();
                    }
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return $storageResult;
    }

    public function processAndCheckJobStorage(): array
    {
        $storageResult = [];
        $jobs = $this->getJobs();
        $progressBar = new ProgressBar($this->output, count($jobs));

        $progressBar->start();

        foreach ($jobs as $job) {
            if (str_contains($job->getPathEnd(), $this->findPath)) {
                $replacePath = str_replace($this->findPath, $this->replacePath, $job->getPathEnd());
                $storageResult[] = [$job->getId(), $job->getPathEnd(), $replacePath];
                if ($this->force) {
                    $job->setPathEnd($replacePath);
                    $this->documentManager->flush();
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
                if (null !== $element['path'] && str_contains($element['path'], $this->findPath)) {
                    $replacePath = str_replace($this->findPath, $this->replacePath, $element['path']);
                    $storageResult[] = [$multimediaObject['_id'], $element['path'], $replacePath];
                    if ($this->force) {
                        $this->documentManager->createQueryBuilder(MultimediaObject::class)
                            ->updateOne()
                            ->field('materials._id')->equals($element['_id'])
                            ->field('materials.$.path')->set($replacePath)
                            ->getQuery()
                            ->execute()
                        ;
                        $this->documentManager->flush();
                    }
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
                if (null !== $element['path'] && str_contains($element['path'], $this->findPath)) {
                    $replacePath = str_replace($this->findPath, $this->replacePath, $element['path']);
                    $storageResult[] = [$multimediaObject['_id'], $element['path'], $replacePath];
                    if ($this->force) {
                        $this->documentManager->createQueryBuilder(MultimediaObject::class)
                            ->updateOne()
                            ->field('pics._id')->equals($element['_id'])
                            ->field('pics.$.path')->set($replacePath)
                            ->getQuery()
                            ->execute()
                        ;
                        $this->documentManager->flush();
                    }
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
        $qb->addAnd($qb->expr()->field('tracks.path')->equals($this->findPathRegex));
        $qb->hydrate(false);

        return $qb->getQuery()->execute();
    }

    public function getJobs()
    {
        $qb = $this->documentManager->createQueryBuilder(Job::class);
        $qb->addAnd($qb->expr()->field('path_end')->equals($this->findPathRegex));

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
            ->setName('pumukit:upgrade:paths')
            ->setDescription('Check DB before execute schema from v4 to v5')
            ->addOption('find', null, InputOption::VALUE_REQUIRED, 'Set the path to find.')
            ->addOption('replace', null, InputOption::VALUE_REQUIRED, 'Set the path to replace.')
            ->addOption('storage', null, InputOption::VALUE_NONE, 'Use this to fix tracks storage.')
            ->addOption('materials', null, InputOption::VALUE_NONE, 'Use this to fix materials storage.')
            ->addOption('pics', null, InputOption::VALUE_NONE, 'Use this to fix pics storage.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Use this option to execute command.')
            ->setHelp(
                <<<'EOT'
The <info>pumukit:upgrade:check</info> check system using filters before execute schema migration.

    Options:
        Storage — Check all tracks storage to find removed or unknown paths saved on DB.

        <info>php bin/console pumukit:upgrade:paths --find="/path/to/replace/" --replace="/new/path/" --storage</info>


        Materials — Check all materials storage to find removed or unknown paths saved on DB.

        <info>php bin/console pumukit:upgrade:check --materials</info>


        Pics — Check all pics storage to find removed or unknown paths saved on DB.

        <info>php bin/console pumukit:upgrade:check --pics</info>
EOT
            )
        ;
    }
}
