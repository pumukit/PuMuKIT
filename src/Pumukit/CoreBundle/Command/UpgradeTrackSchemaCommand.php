<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
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

    private function multimediaObjectsTypeVideoAudio()
    {
        $criteriaType = [MultimediaObject::TYPE_VIDEO, MultimediaObject::TYPE_AUDIO];
        $criteriaStatus = [MultimediaObject::STATUS_PROTOTYPE];

        return $this->createQuery($criteriaType, $criteriaStatus);
    }

    private function multimediaObjectsUnknown()
    {
        $criteriaType = [MultimediaObject::TYPE_UNKNOWN];
        $criteriaStatus = [MultimediaObject::STATUS_PROTOTYPE];

        return $this->createQuery($criteriaType, $criteriaStatus);
    }

    private function createQuery(array $criteriaType, array $criteriaStatus)
    {
        $qb = $this->documentManager->createQueryBuilder(MultimediaObject::class);
        $qb->field('type')->in($criteriaType);
        $qb->field('status')->notIn($criteriaStatus);
        $qb->field('properties.migrate_v5')->exists(false);
        $qb->hydrate(false);

        return $qb->getQuery()->execute();
    }

    private function convertMultimediaObjectsTypeVideoAudio($multimediaObjects): void
    {
        $progressBar = new ProgressBar($this->output, is_countable($multimediaObjects) ? count($multimediaObjects) : 0);
        $progressBar->start();

        $count = 0;
        foreach ($multimediaObjects as $multimediaObject) {
            $newMedias = [];
            $progressBar->advance();
            $this->oldDataTracks = [];
            $object = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => $multimediaObject['_id']]);
            $tracks = $multimediaObject['tracks'] ?? null;
            if (!$tracks) {
                $object->setType(MultimediaObject::TYPE_VIDEO);
                $this->saveDataOnProperty($object, 'No tracks');
                if (0 === ++$count % 50) {
                    $this->documentManager->flush();
                }

                continue;
            }

            foreach ($tracks as $track) {
                if(isset($track['metadata'])) {
                    continue;
                }
                $this->oldDataTracks[] = serialize($track);
                $newMedias[(string) $track['_id']] = $this->createMediaFromTrack($track);
            }

            $object->removeAllMedias();
            foreach ($newMedias as $id => $media) {
                $object->addTrack($media);
                $this->mediaUpdater->updateId($object, $media, $id);
            }

            $this->saveDataOnProperty($object, serialize($this->oldDataTracks));

            if (0 === ++$count % 50) {
                $this->documentManager->flush();
            }
        }

        $this->documentManager->flush();
        $this->documentManager->clear();

        $progressBar->finish();
    }

    private function convertMultimediaObjectsUnknownToVideo($multimediaObjects): void
    {
        $progressBar = new ProgressBar($this->output, is_countable($multimediaObjects) ? count($multimediaObjects) : 0);
        $progressBar->start();
        $count = 0;

        foreach ($multimediaObjects as $multimediaObject) {
            $object = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => $multimediaObject['_id']]);
            $progressBar->advance();
            $object->setType(MultimediaObject::TYPE_VIDEO);
            if (0 === ++$count % 50) {
                $this->documentManager->flush();
            }
        }

        $this->documentManager->flush();

        $table = new Table($this->output);
        $table
            ->setHeaders(['***** Multimedia Objects Type Unknown converted to video  ***** '])
            ->addRow([is_countable($multimediaObjects) ? count($multimediaObjects) : 0])
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
        $originalName = $track['originalName'] ?? '';
        $description = i18nText::create($track['description']);
        $language = $track['language'];
        $tags = Tags::create($track['tags']);
        $hide = $track['hide'];
        $isDownloadable = $track['allowDownload'];
        $views = $track['numview'] ?? 0;

        $url = StorageUrl::create($track['url'] ?? '');
        $path = Path::create($track['path'] ?? '');
        $storage = Storage::create($url, $path);

        $mediaMetadata = VideoAudio::create('{"format":{"duration":"0"}}');

        $media = Track::create($originalName, $description, $language, $tags, $hide, $isDownloadable, $views, $storage, $mediaMetadata);

        $this->documentManager->persist($media);

        return $media;
    }
}
