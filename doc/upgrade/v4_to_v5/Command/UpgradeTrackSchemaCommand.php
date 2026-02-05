<?php

declare(strict_types=1);

namespace Upgrade\Command;

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

    private MediaUpdater $mediaUpdater;
    private $output;

    private $errors = [];

    public function __construct(DocumentManager $documentManager, MediaUpdater $mediaUpdater)
    {
        parent::__construct();
        $this->documentManager = $documentManager;
        $this->countVideo = 0;
        $this->countAudio = 0;
        $this->countUnknown = 0;
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

        if (!$input->getOption('force')) {
            $this->output->writeln('<error>ATTENTION:</error> You must use the --force option to execute this command.');

            return Command::FAILURE;
        }

        $this->migrateByType([MultimediaObject::TYPE_VIDEO, MultimediaObject::TYPE_AUDIO]);

        $this->migrateByType([MultimediaObject::TYPE_UNKNOWN]);

        if (!empty($this->errors)) {
            $this->output->writeln("\n<error>There was error on migration command:</error>");
            $table = new Table($this->output);
            $table
                ->setHeaders(['Multimedia Object ID', 'Error Message'])
                ->setRows(array_map(function ($error) {
                    return explode(': ', $error, 2);
                }, $this->errors))
            ;
            $table->render();
        } else {
            $this->output->writeln("\n<info>Migration command without errors.</info>");
        }

        return Command::SUCCESS;
    }

    private function migrateByType(array $types): void
    {
        $total = $this->getTotalCount($types);
        if (0 === $total) {
            return;
        }

        $this->output->writeln(sprintf("\nProcessing %s...", implode('/', $types)));
        $progressBar = new ProgressBar($this->output, $total);
        $progressBar->start();

        while (true) {
            $multimediaObjectsRaw = $this->fetchBatchRaw($types, 150);

            if (empty($multimediaObjectsRaw)) {
                break;
            }

            foreach ($multimediaObjectsRaw as $mmoArray) {
                try {
                    $object = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => $mmoArray['_id']]);
                    if (!$object instanceof MultimediaObject) {
                        continue;
                    }

                    if (MultimediaObject::TYPE_UNKNOWN === $object->getType()) {
                        $object->setType(MultimediaObject::TYPE_VIDEO);
                    }

                    $this->upgradeTracksFromArray($object, $mmoArray);
                    $progressBar->advance();
                } catch (\Exception $e) {
                    $this->errors[] = "Error en MMO {$mmoArray['_id']}: {$e->getMessage()}";
                }
            }

            $this->documentManager->clear();
        }

        $progressBar->finish();
        $this->output->writeln('');
    }

    private function getTotalCount(array $types): int
    {
        $qb = $this->documentManager->createQueryBuilder(MultimediaObject::class);
        $qb->field('type')->in($types)
            ->field('status')->notIn([MultimediaObject::STATUS_PROTOTYPE])
            ->field('properties.migrate_v5')->exists(false)
        ;

        return (int) $qb->count()->getQuery()->execute();
    }

    private function fetchBatchRaw(array $types, int $limit): array
    {
        $qb = $this->documentManager->createQueryBuilder(MultimediaObject::class);
        $qb->field('type')->in($types)
            ->field('status')->notIn([MultimediaObject::STATUS_PROTOTYPE])
            ->field('properties.migrate_v5')->exists(false)
            ->limit($limit)
            ->hydrate(false)
        ;

        return $qb->getQuery()->execute()->toArray();
    }

    private function upgradeTracksFromArray(MultimediaObject $object, array $mmoArray): void
    {
        $tracksRaw = $mmoArray['tracks'] ?? [];
        if (empty($tracksRaw)) {
            $object->setType(MultimediaObject::TYPE_VIDEO);
            $object->setProperty('migrate_v5', 'No tracks');

            return;
        }

        $newMedias = [];
        $oldDataLog = [];

        foreach ($tracksRaw as $trackArray) {
            if (isset($trackArray['metadata'])) {
                continue;
            }

            $oldDataLog[] = serialize($trackArray);

            $newMedia = $this->createMediaFromTrackArray($trackArray);
            $newMedias[(string) $trackArray['_id']] = $newMedia;
        }

        $object->removeAllMedias();
        foreach ($newMedias as $media) {
            $object->addTrack($media);
        }

        $object->setProperty('migrate_v5', serialize($oldDataLog));

        $this->documentManager->flush();

        foreach ($newMedias as $oldId => $media) {
            $this->mediaUpdater->updateId($object, $media, $oldId);
        }
    }

    private function createMediaFromTrackArray(array $track): MediaInterface
    {
        $url = StorageUrl::create($track['url'] ?? '');
        $path = Path::create($track['path'] ?? '');
        $storage = Storage::create($url, $path);

        $mediaMetadata = VideoAudio::create('{"format":{"duration":"0"}}');

        $media = Track::create(
            $track['originalName'] ?? '',
            i18nText::create($track['description'] ?? []),
            $track['language'] ?? 'es',
            Tags::create($track['tags'] ?? []),
            (bool) ($track['hide'] ?? false),
            (bool) ($track['allowDownload'] ?? false),
            (int) ($track['numview'] ?? 0),
            $storage,
            $mediaMetadata
        );

        $this->documentManager->persist($media);

        return $media;
    }
}
