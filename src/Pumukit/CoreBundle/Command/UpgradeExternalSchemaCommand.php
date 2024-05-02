<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\SchemaBundle\Document\MediaType\External;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\Generic;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Document\ValueObject\Url;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class UpgradeExternalSchemaCommand extends Command
{
    protected DocumentManager $documentManager;
    private i18nService $i18nService;

    public function __construct(DocumentManager $documentManager, i18nService $i18nService)
    {
        parent::__construct();
        $this->documentManager = $documentManager;
        $this->i18nService = $i18nService;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $multimediaObjects = $this->allExternalMultimediaObjects();

        $progressBar = new ProgressBar($output, count($multimediaObjects));
        $progressBar->start();
        $count = 0;
        foreach ($multimediaObjects as $multimediaObject) {
            $progressBar->advance();
            $externalLink = $multimediaObject->getProperty('externalplayer');
            $externalMedia = $this->createExternalMedia($externalLink);
            $multimediaObject->addExternal($externalMedia);
            $multimediaObject->removeProperty('externalplayer');

            if (0 === ++$count % 50) {
                $this->documentManager->flush();
            }
        }
        $this->documentManager->flush();
        $this->documentManager->clear();

        $progressBar->finish();

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:upgrade:schema:external')
            ->setDescription('Upgrade schema of tracks from v4 to v5')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Use this to execute command')
            ->setHelp(
                <<<'EOT'
The <info>pumukit:schema:upgrade:external</info> upgrade external property to new media schema

  <info>php app/console pumukit:schema:upgrade:external --force</info>
EOT
            )
        ;
    }

    private function allExternalMultimediaObjects(): array
    {
        return $this->documentManager->getRepository(MultimediaObject::class)->findBy(
            [
                'properties.externalplayer' => ['$exists' => true]
            ]
        );
    }

    private function createExternalMedia(string $externalLink): MediaInterface
    {
        $originalName = '';
        $description = i18nText::create($this->i18nService->generateI18nText(''));
        $language = '';
        $tags = Tags::create(['display']);
        $url = Url::create($externalLink);
        $storage = Storage::external($url);
        $metadata = Generic::create('');
        $external = External::create($originalName, $description, $language, $tags, false, false, 0, $storage, $metadata);

        $this->documentManager->persist($external);

        return $external;
    }
}
