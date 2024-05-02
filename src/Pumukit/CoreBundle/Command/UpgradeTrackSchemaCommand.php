<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Services\MediaUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class UpgradeTrackSchemaCommand extends Command
{
    protected DocumentManager $documentManager;

    protected int $countVideo;
    protected int $countAudio;
    protected int $countUnknown;

    private array $oldDataTracks;
    private MediaUpdater $mediaUpdater;
    private $output;

    public function __construct(DocumentManager $documentManager, MediaUpdater $mediaUpdater)
    {
        parent::__construct();
        $this->documentManager = $documentManager;
        $this->countVideo = 0;
        $this->countAudio = 0;
        $this->countUnknown = 0;
        $this->oldDataTracks = [];
        $this->mediaUpdater = $mediaUpdater;
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:upgrade:schema:track')
            ->setDescription('Upgrade schema of tracks from v4 to v5')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Use this to execute command')
            ->setHelp(
                <<<'EOT'
The <info>pumukit:schema:upgrade:track</info> upgrade track schema to new media schema

  <info>php app/console pumukit:schema:upgrade:track --force</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $multimediaObjects = $this->multimediaObjectsTypeVideoAudio();
        $this->convertMultimediaObjectsTypeVideoAudio($multimediaObjects);

        $multimediaObjects = $this->multimediaObjectsUnknown();
        $this->convertMultimediaObjectsUnknownToVideo($multimediaObjects);

        return Command::SUCCESS;
    }

    private function multimediaObjectsTypeVideoAudio(): array
    {
        $criteriaType = [MultimediaObject::TYPE_VIDEO, MultimediaObject::TYPE_AUDIO];
        $criteriaStatus = [MultimediaObject::STATUS_PROTOTYPE];

        return $this->createQuery($criteriaType, $criteriaStatus);
    }

    private function multimediaObjectsUnknown(): array
    {
        $criteriaType = [MultimediaObject::TYPE_UNKNOWN];
        $criteriaStatus = [MultimediaObject::STATUS_PROTOTYPE];

        return $this->createQuery($criteriaType, $criteriaStatus);
    }

    private function createQuery(array $criteriaType, array $criteriaStatus): array
    {
        $qb = $this->documentManager->createQueryBuilder(MultimediaObject::class);
        $qb->field('type')->in($criteriaType);
        $qb->field('status')->notIn($criteriaStatus);
        $qb->field('properties.migrate_v5')->exists(false);

        return $qb->getQuery()->execute();
    }

    private function convertMultimediaObjectsTypeVideoAudio(array $multimediaObjects): void
    {
        $progressBar = new ProgressBar($this->output, count($multimediaObjects));
        $progressBar->start();

        $count = 0;
        foreach ($multimediaObjects as $multimediaObject) {
            $progressBar->advance();
            $tracks = $multimediaObject->getTracks();
            if (!$tracks) {
                $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
                $this->saveDataOnProperty($multimediaObject, 'No tracks');
                if (0 === ++$count % 50) {
                    $this->documentManager->flush();
                }

                continue;
            }

            foreach ($tracks as $track) {
                $this->oldDataTracks[] = serialize($track);
                $this->saveDataOnProperty($multimediaObject, serialize($track));
                $media = $this->createMediaFromTrack($track);
                $trackID = (string) $track['id'];
                $multimediaObject->removeTrackById($trackID);

                $this->mediaUpdater->updateId($multimediaObject, $media, $trackID);
                $multimediaObject->addTrack($media);
            }

            if (0 === ++$count % 50) {
                $this->documentManager->flush();
            }
        }

        $this->documentManager->flush();
        $this->documentManager->clear();

        $progressBar->finish();
    }

    private function convertMultimediaObjectsUnknownToVideo(array $multimediaObjects): void
    {
        $progressBar = new ProgressBar($this->output, count($multimediaObjects));
        $progressBar->start();
        $count = 0;

        foreach ($multimediaObjects as $multimediaObject) {
            $progressBar->advance();
            $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
            if (0 === ++$count % 50) {
                $this->documentManager->flush();
            }
        }

        if (0 === ++$count % 50) {
            $this->documentManager->flush();
        }

        $table = new Table($this->output);
        $table
            ->setHeaders(['***** Multimedia Objects Type Unknown converted to video  ***** '])
            ->addRow([count($multimediaObjects)])
        ;

        $table->render();

        $progressBar->finish();
    }

    private function saveDataOnProperty(MultimediaObject $multimediaObject, string $data): void
    {
        $multimediaObject->setProperty('migrate_v5', $data);
    }

    private function createMediaFromTrack(array $track): MediaInterface
    {
        $originalName = $track['originalName'];
        $description = i18nText::create($track['description']);
        $language = $track['language'];
        $tags = $track['tags'];
        $hide = $track['hide'];
        $isDownloadable = $track['allowDownload'];
        $views = $track['numview'];

        $storage = $track['storage'];
        $mediaMetadata = VideoAudio::create('');
        $media = Track::create($originalName, $description, $language, $tags, $hide, $isDownloadable, $views, $storage, $mediaMetadata);

        $this->documentManager->persist($media);

        return $media;
    }
}
