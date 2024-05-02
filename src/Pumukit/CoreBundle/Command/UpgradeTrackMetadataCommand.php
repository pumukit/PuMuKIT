<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\MediaUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class UpgradeTrackMetadataCommand extends Command
{
    protected DocumentManager $documentManager;
    protected InspectionFfprobeService $inspectionFfprobeService;

    protected MediaUpdater $mediaUpdater;

    public function __construct(DocumentManager $documentManager, InspectionFfprobeService $inspectionFfprobeService, MediaUpdater $mediaUpdater)
    {
        $this->documentManager = $documentManager;
        $this->inspectionFfprobeService = $inspectionFfprobeService;
        $this->mediaUpdater = $mediaUpdater;
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

        $progressBar = new ProgressBar($output, count($multimediaObjects));
        $progressBar->start();

        $count = 0;
        foreach ($multimediaObjects as $multimediaObject) {
            $progressBar->advance();

            $this->upgradeMetadata($multimediaObject);

            if (0 === ++$count % 50) {
                $this->documentManager->flush();
            }
        }

        $progressBar->finish();
        $this->documentManager->flush();
        $this->documentManager->clear();

        return Command::SUCCESS;
    }

    private function multimediaObjectsTypeVideoAudio(): array
    {
        $criteriaType = [MultimediaObject::TYPE_VIDEO, MultimediaObject::TYPE_AUDIO];
        $criteriaStatus = [MultimediaObject::STATUS_PROTOTYPE];

        return $this->createQuery($criteriaType, $criteriaStatus);
    }

    private function createQuery(array $criteriaType, array $criteriaStatus): array
    {
        $qb = $this->documentManager->createQueryBuilder(MultimediaObject::class);
        $qb->field('properties.migrate_v5')->exists(true);
        $qb->field('properties.migrate_v5_metadata_extract')->equals(false);

        return $qb->getQuery()->execute();
    }

    private function upgradeMetadata(MultimediaObject $multimediaObject): void
    {
        foreach ($multimediaObject->getTracks() as $track) {
            $data = $this->inspectionFfprobeService->getFileMetadataAsString($track->storage()->path());
            $mediaMetadata = VideoAudio::create($data);
            $this->mediaUpdater->updateMetadata($multimediaObject, $track, $mediaMetadata);
        }
    }
}
