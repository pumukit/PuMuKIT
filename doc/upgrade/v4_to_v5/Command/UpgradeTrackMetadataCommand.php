<?php

declare(strict_types=1);

namespace Upgrade\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\MediaUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class UpgradeTrackMetadataCommand extends Command
{
    protected DocumentManager $documentManager;
    protected InspectionFfprobeService $inspectionFfprobeService;

    protected MediaUpdater $mediaUpdater;

    protected array $errors;

    public function __construct(DocumentManager $documentManager, InspectionFfprobeService $inspectionFfprobeService, MediaUpdater $mediaUpdater)
    {
        $this->documentManager = $documentManager;
        $this->inspectionFfprobeService = $inspectionFfprobeService;
        $this->mediaUpdater = $mediaUpdater;
        $this->errors = [];
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:upgrade:metadata:track')
            ->setDescription('Upgrade metadata of tracks.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Use this to execute command')
            ->setHelp(
                <<<'EOT'
The <info>pumukit:upgrade:metadata:track</info> upgrade track schema to new media schema

  <info>php app/console pumukit:upgrade:metadata:track --force</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $multimediaObjects = $this->multimediaObjectsTypeVideoAudio();

        if ((is_countable($multimediaObjects) ? count($multimediaObjects) : 0) === 0) {
            $output->writeln('No multimedia objects found.');

            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, is_countable($multimediaObjects) ? count($multimediaObjects) : 0);
        $progressBar->start();

        $count = 0;
        foreach ($multimediaObjects as $multimediaObject) {
            $progressBar->advance();

            $this->upgradeMetadata($multimediaObject);
            $multimediaObject->setProperty('migrate_v5_metadata_extract', true);

            if (0 === ++$count % 50) {
                $this->documentManager->flush();
            }
        }

        $progressBar->finish();
        $this->documentManager->flush();
        $this->documentManager->clear();

        $output->writeln('');
        $table = new Table($output);
        $table->setHeaders(['MultimediaObject', 'Track', 'Path']);
        foreach ($this->errors as $multimediaObjectId => $tracks) {
            foreach ($tracks as $track) {
                $table->addRow([$multimediaObjectId, $track->getId(), $track->storage()->path()]);
            }
        }

        $table->render();

        return Command::SUCCESS;
    }

    private function multimediaObjectsTypeVideoAudio()
    {
        $criteriaType = [MultimediaObject::TYPE_VIDEO, MultimediaObject::TYPE_AUDIO];
        $criteriaStatus = [MultimediaObject::STATUS_PROTOTYPE];

        return $this->createQuery($criteriaType, $criteriaStatus);
    }

    private function createQuery(array $criteriaType, array $criteriaStatus)
    {
        $qb = $this->documentManager->createQueryBuilder(MultimediaObject::class);
        $qb->field('type')->in($criteriaType);
        $qb->field('status')->notIn($criteriaStatus);
        $qb->field('properties.migrate_v5')->exists(true);
        $qb->field('properties.migrate_v5_metadata_extract')->exists(false);

        return $qb->getQuery()->execute();
    }

    private function upgradeMetadata(MultimediaObject $multimediaObject): void
    {
        foreach ($multimediaObject->getTracks() as $track) {
            try {
                $data = $this->inspectionFfprobeService->getFileMetadataAsString($track->storage()->path());
                $mediaMetadata = VideoAudio::create($data);
                $this->mediaUpdater->updateMetadata($multimediaObject, $track, $mediaMetadata);
            } catch (\Exception $e) {
                $this->errors[$multimediaObject->getId()][] = $track;
            }
        }
    }
}
